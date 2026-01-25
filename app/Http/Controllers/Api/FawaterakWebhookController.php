<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Services\FawaterakService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FawaterakWebhookController extends Controller
{
    protected $fawaterakService;

    public function __construct(FawaterakService $fawaterakService)
    {
        $this->fawaterakService = $fawaterakService;
    }

    /**
     * Handle Webhook from Fawaterak
     */
    public function webhook(Request $request)
    {
        try {
            Log::info('Fawaterak Webhook Received', $request->all());

            $invoiceId = $request->input('invoice_id');
            $status = $request->input('status');

            if (!$invoiceId) {
                return response()->json(['message' => 'Invalid data'], 400);
            }

            // Find transaction by invoice ID
            $transaction = WalletTransaction::where('fawaterak_invoice_id', $invoiceId)->first();

            if (!$transaction) {
                Log::warning('Transaction not found for invoice', ['invoice_id' => $invoiceId]);
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            // Update transaction based on status
            if ($status == 'paid' || $status == 'success') {
                $this->handleSuccessfulPayment($transaction);
            } else {
                $this->handleFailedPayment($transaction, $status);
            }

            return response()->json(['message' => 'Webhook processed successfully'], 200);

        } catch (\Exception $e) {
            Log::error('Fawaterak Webhook Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error processing webhook'], 500);
        }
    }

    /**
     * Handle Callback from Fawaterak (user redirect)
     */
    public function callback(Request $request)
    {
        try {
            Log::info('Fawaterak Callback Received', $request->all());

            $invoiceId = $request->input('invoice_id') ?? $request->input('fawaterk_invoice_id');
            
            if (!$invoiceId) {
                return redirect(config('fawaterak.failure_url') . '?error=invalid_data');
            }

            // Find transaction
            $transaction = WalletTransaction::where('fawaterak_invoice_id', $invoiceId)->first();

            if (!$transaction) {
                return redirect(config('fawaterak.failure_url') . '?error=transaction_not_found');
            }

            // Get invoice status from Fawaterak
            $statusResult = $this->fawaterakService->getInvoiceStatus($invoiceId);

            if ($statusResult['success']) {
                $invoiceData = $statusResult['data'];
                $paymentStatus = $invoiceData['data']['status'] ?? $invoiceData['status'] ?? 'unknown';

                if ($paymentStatus == 'paid' || $paymentStatus == 'success') {
                    $this->handleSuccessfulPayment($transaction);
                    return redirect(config('fawaterak.success_url') . '?transaction=' . $transaction->reference_number);
                }
            }

            return redirect(config('fawaterak.failure_url') . '?error=payment_failed');

        } catch (\Exception $e) {
            Log::error('Fawaterak Callback Error', [
                'message' => $e->getMessage(),
            ]);
            return redirect(config('fawaterak.failure_url') . '?error=system_error');
        }
    }

    /**
     * Handle successful payment
     */
    private function handleSuccessfulPayment(WalletTransaction $transaction)
    {
        if ($transaction->status === 'completed') {
            return; // Already processed
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
            ]);

            DB::commit();

            Log::info('Payment Completed Successfully', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'amount' => $transaction->amount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error handling successful payment', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle failed payment
     */
    private function handleFailedPayment(WalletTransaction $transaction, $status)
    {
        $transaction->update([
            'status' => 'failed',
            'notes' => 'Payment failed with status: ' . $status,
        ]);

        Log::warning('Payment Failed', [
            'transaction_id' => $transaction->id,
            'status' => $status,
        ]);
    }

    /**
     * Handle Test Mode Callback (Simulate Payment)
     */
    public function testCallback(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $status = $request->input('status');

        if ($status !== 'paid') {
            return redirect(config('fawaterak.failure_url') . '?error=payment_cancelled');
        }

        $transaction = WalletTransaction::find($transactionId);

        if (!$transaction) {
            return redirect(config('fawaterak.failure_url') . '?error=transaction_not_found');
        }

        // Process successful payment
        $this->handleSuccessfulPayment($transaction);

        return redirect(config('fawaterak.success_url') . '?transaction=' . $transaction->reference_number);
    }
}
