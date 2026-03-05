<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Services\MoyasarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MoyasarWebhookController extends Controller
{
    protected $moyasarService;

    public function __construct(MoyasarService $moyasarService)
    {
        $this->moyasarService = $moyasarService;
    }

    /**
     * Handle Callback from Moyasar (Success/Failure Redirect)
     */
    public function callback(Request $request)
    {
        try {
            Log::info('Moyasar Callback Received', $request->all());

            $paymentId = $request->input('id');
            $status    = $request->input('status');

            if (!$paymentId || $status !== 'paid') {
                return $this->failurePage();
            }

            // Verify payment status with Moyasar (handles DEBUG_SUCCESS internally)
            $statusResult = $this->moyasarService->getPaymentStatus($paymentId);

            if ($statusResult['success'] && $statusResult['data']['status'] === 'paid') {
                $referenceNumber = $statusResult['data']['reference']
                    ?? $request->input('reference'); // fallback for debug mode

                $transaction = WalletTransaction::where('reference_number', $referenceNumber)->first();

                if ($transaction) {
                    $this->handleSuccessfulPayment($transaction, $paymentId);

                    // Reload fresh balance — works for both new and already-completed transactions
                    $transaction->refresh();
                    $transaction->load('user');
                    $newBalance = $transaction->user->wallet_balance;

                    return $this->successPage($newBalance, $referenceNumber);
                }
            }

            return $this->failurePage();

        } catch (\Exception $e) {
            Log::error('Moyasar Callback Error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function successPage($balance, $reference)
    {
        return response("
            <!DOCTYPE html>
            <html lang='ar' dir='rtl'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>تم الدفع بنجاح</title>
                <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap' rel='stylesheet'>
                <style>
                    body { font-family: 'Cairo', sans-serif; background: linear-gradient(135deg,#e8f5e9,#f0f4ff); display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
                    .card { background:#fff; padding:40px; border-radius:24px; box-shadow:0 12px 40px rgba(0,0,0,0.12); text-align:center; max-width:420px; width:90%; }
                    .icon { font-size:64px; margin-bottom:16px; }
                    h1 { color:#2e7d32; margin:0 0 8px; font-size:24px; }
                    p { color:#666; margin:0 0 24px; }
                    .balance-box { background:#e8f5e9; padding:20px; border-radius:16px; margin-bottom:20px; }
                    .balance-label { font-size:13px; color:#4caf50; display:block; margin-bottom:4px; }
                    .balance-amount { font-size:36px; font-weight:bold; color:#2e7d32; }
                    .ref { font-size:12px; color:#aaa; margin-top:16px; }
                </style>
            </head>
            <body>
                <div class='card'>
                    <div class='icon'>✅</div>
                    <h1>تم الشحن بنجاح!</h1>
                    <p>شكراً لك، تمت إضافة المبلغ لمحفظتك فوراً.</p>
                    <div class='balance-box'>
                        <span class='balance-label'>رصيدك الحالي</span>
                        <span class='balance-amount'>{$balance} ج.م</span>
                    </div>
                    <div class='ref'>رقم العملية: {$reference}</div>
                </div>
            </body>
            </html>
        ");
    }

    private function failurePage()
    {
        return response("
            <!DOCTYPE html>
            <html lang='ar' dir='rtl'>
            <head>
                <meta charset='UTF-8'>
                <title>فشل الدفع</title>
                <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap' rel='stylesheet'>
                <style>
                    body { font-family: 'Cairo', sans-serif; background: linear-gradient(135deg,#fff5f5,#fff); display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
                    .card { background:#fff; padding:40px; border-radius:24px; box-shadow:0 12px 40px rgba(0,0,0,0.1); text-align:center; max-width:420px; width:90%; }
                    .icon { font-size:64px; margin-bottom:16px; }
                    h1 { color:#c62828; margin:0 0 8px; }
                    p { color:#666; }
                </style>
            </head>
            <body>
                <div class='card'>
                    <div class='icon'>❌</div>
                    <h1>فشل الدفع</h1>
                    <p>نعتذر، لم نتمكن من إتمام العملية. يرجى المحاولة مرة أخرى.</p>
                </div>
            </body>
            </html>
        ", 400);
    }

    /**
     * Handle Webhook from Moyasar
     */
    public function webhook(Request $request)
    {
        Log::info('Moyasar Webhook Received', $request->all());
        
        $data = $request->all();
        $type = $data['type'] ?? '';

        if ($type === 'payment.captured' || $type === 'payment.paid') {
            $paymentId = $data['data']['id'];
            $referenceNumber = $data['data']['metadata']['reference_number'] ?? null;
            
            if ($referenceNumber) {
                $transaction = WalletTransaction::where('reference_number', $referenceNumber)->first();
                if ($transaction) {
                    $this->handleSuccessfulPayment($transaction, $paymentId);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle successful payment (Update balance)
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
                'notes' => 'Paid via Moyasar. Payment ID: ' . $paymentId,
            ]);

            DB::commit();

            Log::info('Moyasar Payment Completed Successfully', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error handling Moyasar successful payment', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
