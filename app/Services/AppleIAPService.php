<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service to verify Apple In-App Purchase receipts with Apple's servers.
 *
 * Flow:
 *  1. Try production endpoint first.
 *  2. If Apple returns status 21007 (sandbox receipt sent to production),
 *     retry with the sandbox endpoint.
 *
 * Apple status codes:
 *   0    = Valid receipt
 *   21007 = Sandbox receipt sent to production → retry with sandbox
 *   21002 = Malformed data
 *   21003 = Receipt could not be authenticated
 *   21008 = Production receipt sent to sandbox
 */
class AppleIAPService
{
    private const PRODUCTION_URL = 'https://buy.itunes.apple.com/verifyReceipt';
    private const SANDBOX_URL    = 'https://sandbox.itunes.apple.com/verifyReceipt';

    /**
     * Map product IDs to SAR amounts.
     * Must match App Store Connect product IDs.
     */
    public const PRODUCT_AMOUNTS = [
        'swalaf_deposit_10'  => 10.00,
        'swalaf_deposit_25'  => 25.00,
        'swalaf_deposit_50'  => 50.00,
        'swalaf_deposit_100' => 100.00,
    ];

    /**
     * Verify a receipt with Apple and extract the purchase for a given product.
     *
     * @param  string $receiptData  Base64-encoded receipt from the Flutter IAP plugin
     * @param  string $productId    The product ID the user claims to have purchased
     * @return array  ['success' => bool, 'amount' => float|null, 'transaction_id' => string|null, 'error' => string|null]
     */
    public function verify(string $receiptData, string $productId): array
    {
        // 1. Try production first
        $result = $this->callApple(self::PRODUCTION_URL, $receiptData);

        // 2. If sandbox receipt was sent to production, retry with sandbox
        if (isset($result['status']) && $result['status'] === 21007) {
            Log::info('Apple IAP: Sandbox receipt detected, retrying with sandbox URL');
            $result = $this->callApple(self::SANDBOX_URL, $receiptData);
        }

        // 3. Check Apple's status
        if (!isset($result['status']) || $result['status'] !== 0) {
            $errorMsg = $this->getStatusMessage($result['status'] ?? -1);
            Log::warning('Apple IAP verification failed', [
                'product_id' => $productId,
                'status'     => $result['status'] ?? 'unknown',
            ]);
            return ['success' => false, 'error' => $errorMsg];
        }

        // 4. Find matching purchase in receipt
        $inAppPurchases = $result['receipt']['in_app'] ?? [];
        $matchedPurchase = null;

        // Find the most recent purchase of this product
        foreach ($inAppPurchases as $purchase) {
            if ($purchase['product_id'] === $productId) {
                if (
                    $matchedPurchase === null ||
                    ($purchase['purchase_date_ms'] ?? 0) > ($matchedPurchase['purchase_date_ms'] ?? 0)
                ) {
                    $matchedPurchase = $purchase;
                }
            }
        }

        if (!$matchedPurchase) {
            Log::warning('Apple IAP: product not found in receipt', ['product_id' => $productId]);
            return ['success' => false, 'error' => 'Product not found in receipt'];
        }

        // 5. Determine the SAR amount from product ID
        $amount = self::PRODUCT_AMOUNTS[$productId] ?? null;
        if (!$amount) {
            return ['success' => false, 'error' => 'Unknown product ID: ' . $productId];
        }

        return [
            'success'        => true,
            'amount'         => $amount,
            'transaction_id' => $matchedPurchase['transaction_id'] ?? null,
            'original_transaction_id' => $matchedPurchase['original_transaction_id'] ?? null,
            'purchase_date'  => $matchedPurchase['purchase_date'] ?? null,
        ];
    }

    /**
     * Call Apple's verifyReceipt endpoint.
     */
    private function callApple(string $url, string $receiptData): array
    {
        try {
            $sharedSecret = config('services.apple_iap.shared_secret');

            $payload = ['receipt-data' => $receiptData];
            if ($sharedSecret) {
                $payload['password'] = $sharedSecret;
            }

            $response = Http::timeout(15)
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::error('Apple IAP HTTP error', ['status' => $response->status()]);
                return ['status' => -1];
            }

            return $response->json() ?? ['status' => -1];

        } catch (\Exception $e) {
            Log::error('Apple IAP request failed: ' . $e->getMessage());
            return ['status' => -1];
        }
    }

    /**
     * Human-readable messages for Apple status codes.
     */
    private function getStatusMessage(int $status): string
    {
        return match ($status) {
            0     => 'Valid receipt',
            21000 => 'App Store could not read the JSON',
            21002 => 'Malformed receipt data',
            21003 => 'Receipt authentication failed',
            21004 => 'Shared secret mismatch',
            21005 => 'Receipt server is unavailable',
            21006 => 'Subscription has expired',
            21007 => 'Sandbox receipt sent to production',
            21008 => 'Production receipt sent to sandbox',
            21010 => 'This receipt could not be authorized',
            default => "Unknown error (status: {$status})",
        };
    }
}
