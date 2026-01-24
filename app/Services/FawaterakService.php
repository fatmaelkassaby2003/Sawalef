<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FawaterakService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('fawaterak.api_key');
        $this->baseUrl = config('fawaterak.base_url');
    }

    /**
     * Create a new payment invoice
     * 
     * @param array $data
     * @return array
     */
    public function createInvoice(array $data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/invoiceInitPay', [
                'payment_method_id' => $data['payment_method_id'] ?? 1, // Default: Card
                'cartTotal' => $data['amount'],
                'currency' => 'EGP',
                'customer' => [
                    'first_name' => $data['customer_name'],
                    'last_name' => '',
                    'email' => $data['customer_email'],
                    'phone' => $data['customer_phone'],
                    'address' => ''
                ],
                'redirectionUrls' => [
                    'successUrl' => config('fawaterak.success_url'),
                    'failUrl' => config('fawaterak.failure_url'),
                    'pendingUrl' => config('fawaterak.failure_url')
                ],
                'cartItems' => [[
                    'name' => $data['item_name'],
                    'price' => $data['amount'],
                    'quantity' => 1
                ]],
                'vendorId' => $data['vendor_id'] ?? null,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Fawaterak Invoice Created', ['response' => $result]);
                return [
                    'success' => true,
                    'data' => $result['data'] ?? $result,
                ];
            }

            Log::error('Fawaterak Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'فشل في إنشاء فاتورة الدفع',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('Fawaterak Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الفاتورة: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get invoice status
     * 
     * @param string $invoiceId
     * @return array
     */
    public function getInvoiceStatus($invoiceId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/getInvoiceData/' . $invoiceId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'فشل في الحصول على بيانات الفاتورة'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify payment callback
     * 
     * @param array $data
     * @return bool
     */
    public function verifyPayment(array $data)
    {
        // يمكنك إضافة المزيد من التحقق هنا حسب احتياجاتك
        return isset($data['invoice_id']) && isset($data['status']);
    }
}
