<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoyasarService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey  = config('moyasar.api_key');
        $this->baseUrl = rtrim(config('moyasar.base_url'), '/');

        if (empty($this->apiKey)) {
            Log::error('Moyasar API Key is missing!');
        }
    }

    /**
     * Create Moyasar Invoice and return checkout URL
     */
    public function createInvoice($data)
    {
        // ===== INSTANT SUCCESS DEBUG MODE =====
        if (config('moyasar.instant_success')) {
            $callbackUrl = config('moyasar.success_url');
            // Append debug params to callback URL
            $separator = str_contains($callbackUrl, '?') ? '&' : '?';
            $debugUrl  = $callbackUrl . $separator . 'id=DEBUG_SUCCESS&status=paid&reference=' . $data['reference_number'];
            Log::info('Moyasar INSTANT SUCCESS link generated', ['url' => $debugUrl]);
            return [
                'success'     => true,
                'payment_url' => $debugUrl,
                'invoice_id'  => 'DEBUG_INVOICE',
            ];
        }
        // ====================================

        try {
            // Moyasar expects amount in the SMALLEST CURRENCY UNIT
            // For SAR/EGP: 1 unit = 100 halala/piasters
            $amountInSmallUnit = (int)($data['amount'] * 100);

            $payload = [
                'amount'       => $amountInSmallUnit,
                'currency'     => $data['currency'] ?? config('moyasar.currency', 'SAR'),
                'description'  => $data['description'] ?? 'Wallet Deposit - ' . $data['reference_number'],
                'callback_url' => config('moyasar.success_url'),
                'metadata'     => [
                    'reference_number' => $data['reference_number'],
                    'customer_name'    => $data['customer_name'],
                    'customer_email'   => $data['customer_email'],
                ],
            ];

            $response = Http::withBasicAuth($this->apiKey, '')
                ->acceptJson()
                ->post($this->baseUrl . '/invoices', $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Moyasar Invoice Created', ['id' => $result['id'], 'url' => $result['url']]);
                return [
                    'success'     => true,
                    'payment_url' => $result['url'],
                    'invoice_id'  => $result['id'],
                ];
            }

            Log::error('Moyasar Create Invoice Error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'فشل في إنشاء فاتورة الدفع',
                'error'   => $response->json() ?: $response->body(),
            ];

        } catch (\Exception $e) {
            Log::error('Moyasar Exception', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء الاتصال بميسر',
            ];
        }
    }

    /**
     * Get Payment Info from Moyasar (by payment ID)
     */
    public function getPaymentStatus($paymentId)
    {
        // ===== INSTANT SUCCESS DEBUG MODE =====
        if ($paymentId === 'DEBUG_SUCCESS' && config('moyasar.instant_success')) {
            Log::info('Moyasar DEBUG_SUCCESS payment status override');
            return [
                'success' => true,
                'data'    => [
                    'status'    => 'paid',
                    'amount'    => 0,
                    'reference' => request()->input('reference'),
                ],
            ];
        }
        // ====================================

        try {
            $response = Http::withBasicAuth($this->apiKey, '')
                ->get($this->baseUrl . '/payments/' . $paymentId);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'data'    => [
                        'status'    => $result['status'],
                        'amount'    => $result['amount'] / 100,
                        'reference' => $result['metadata']['reference_number'] ?? null,
                    ],
                ];
            }

            Log::error('Moyasar Get Payment Error', ['status' => $response->status(), 'body' => $response->body()]);
            return ['success' => false, 'message' => 'فشل في جلب حالة الدفع'];

        } catch (\Exception $e) {
            Log::error('Moyasar Status Exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'حدث خطأ أثناء جلب حالة الدفع'];
        }
    }
}
