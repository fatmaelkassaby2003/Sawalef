<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Services\MyFatoorahService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MyFatoorahWebhookController extends Controller
{
    protected $myFatoorahService;

    public function __construct(MyFatoorahService $myFatoorahService)
    {
        $this->myFatoorahService = $myFatoorahService;
    }

    /**
     * Handle Callback from MyFatoorah (Success/Failure Redirect)
     */
    public function callback(Request $request)
    {
        try {
            Log::info('MyFatoorah Callback Received', $request->all());

            $paymentId = $request->input('paymentId');
            
            if (!$paymentId) {
                return redirect(config('myfatoorah.failure_url') . '&error=invalid_data');
            }

            // Get payment status from MyFatoorah
            $statusResult = $this->myFatoorahService->getPaymentStatus($paymentId);

            if ($statusResult['success']) {
                $paymentData = $statusResult['data'];
                $invoiceStatus = $paymentData['InvoiceStatus'];
                $referenceNumber = $paymentData['CustomerReference'];

                // Find transaction
                $transaction = WalletTransaction::where('reference_number', $referenceNumber)->first();

                if (!$transaction) {
                    Log::error('Transaction not found for reference', ['reference' => $referenceNumber]);
                    return redirect(config('myfatoorah.failure_url') . '&error=transaction_not_found');
                }

                if ($invoiceStatus === 'Paid') {
                    $this->handleSuccessfulPayment($transaction, $paymentId);
                    $newBalance = $transaction->user->wallet_balance;
                    
                    return response("
                        <!DOCTYPE html>
                        <html lang='ar' dir='rtl'>
                        <head>
                            <meta charset='UTF-8'>
                            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                            <title>تم الدفع بنجاح</title>
                            <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap' rel='stylesheet'>
                            <style>
                                body { font-family: 'Cairo', sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                                .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; max-width: 400px; width: 90%; }
                                .icon { font-size: 60px; color: #4CAF50; margin-bottom: 20px; }
                                h1 { color: #333; margin-bottom: 10px; }
                                p { color: #666; font-size: 18px; margin-bottom: 30px; }
                                .balance-box { background: #e8f5e9; padding: 20px; border-radius: 12px; margin-bottom: 30px; }
                                .balance-label { font-size: 14px; color: #4CAF50; display: block; }
                                .balance-amount { font-size: 32px; font-weight: bold; color: #2e7d32; }
                                .ref { font-size: 12px; color: #aaa; }
                            </style>
                        </head>
                        <body>
                            <div class='card'>
                                <div class='icon'>✅</div>
                                <h1>تم الشحن بنجاح!</h1>
                                <p>شكراً لك، تم إضافة المبلغ لمحفظتك فوراً.</p>
                                <div class='balance-box'>
                                    <span class='balance-label'>رصيدك الحالي</span>
                                    <span class='balance-amount'>{$newBalance} ج.م</span>
                                </div>
                                <div class='ref'>رقم العملية: {$referenceNumber}</div>
                            </div>
                        </body>
                        </html>
                    ");
                }
            }

            return response("
                <!DOCTYPE html>
                <html lang='ar' dir='rtl'>
                <head>
                    <meta charset='UTF-8'>
                    <title>خطأ في الدفع</title>
                    <style>
                        body { font-family: sans-serif; background: #fff5f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; }
                        .icon { font-size: 60px; color: #f44336; }
                    </style>
                </head>
                <body>
                    <div class='card'>
                        <div class='icon'>❌</div>
                        <h1>فشل الدفع</h1>
                        <p>نعتذر، لم نتمكن من إتمام العملية.</p>
                    </div>
                </body>
                </html>
            ", 400);

        } catch (\Exception $e) {
            Log::error('MyFatoorah Callback Error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle Webhook from MyFatoorah
     */
    public function webhook(Request $request)
    {
        // MyFatoorah webhooks require a secret key verification usually.
        // For simplicity, we'll implement the basic logic.
        Log::info('MyFatoorah Webhook Received', $request->all());
        
        $data = $request->input('Data');
        if (!$data) return response()->json(['status' => 'error'], 400);

        $paymentId = $data['PaymentId'] ?? null;
        if (!$paymentId) return response()->json(['status' => 'error'], 400);

        $statusResult = $this->myFatoorahService->getPaymentStatus($paymentId);
        
        if ($statusResult['success']) {
            $paymentData = $statusResult['data'];
            if ($paymentData['InvoiceStatus'] === 'Paid') {
                $transaction = WalletTransaction::where('reference_number', $paymentData['CustomerReference'])->first();
                if ($transaction) {
                    $this->handleSuccessfulPayment($transaction, $paymentId);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle successful payment
     */
    private function handleSuccessfulPayment(WalletTransaction $transaction, $paymentId)
    {
        if ($transaction->status === 'completed') {
            return;
        }

        DB::beginTransaction();

        try {
            $user = $transaction->user;
            $newBalance = $user->wallet_balance + $transaction->amount;

            // Update user balance
            $user->update([
                'wallet_balance' => $newBalance
            ]);

            // Update transaction
            $transaction->update([
                'status' => 'completed',
                'balance_after' => $newBalance,
                'gateway_invoice_id' => $paymentId,
                'notes' => 'Paid via MyFatoorah. Payment ID: ' . $paymentId,
            ]);

            DB::commit();

            Log::info('MyFatoorah Payment Completed Successfully', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error handling MyFatoorah successful payment', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
