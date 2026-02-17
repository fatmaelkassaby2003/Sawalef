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
                    return redirect(env('FRONTEND_URL', 'https://sawalef.com') . '/payment/success?reference=' . $referenceNumber);
                }
            }

            return redirect(env('FRONTEND_URL', 'https://sawalef.com') . '/payment/failed?error=payment_failed');

        } catch (\Exception $e) {
            Log::error('MyFatoorah Callback Error', ['message' => $e->getMessage()]);
            return redirect(env('FRONTEND_URL', 'https://sawalef.com') . '/payment/failed?error=system_error');
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
