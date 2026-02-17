<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MyFatoorahService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('myfatoorah.api_key');
        $this->baseUrl = config('myfatoorah.base_url');

        if (empty($this->apiKey)) {
            Log::error('MyFatoorah API Key is missing in configuration!');
        }
    }

    /**
     * Send Payment Request
     */
    public function sendPayment($data)
    {
        try {
            $payload = [
                'NotificationOption' => 'LNK', // Get Payment URL
                'InvoiceValue'       => $data['amount'],
                'CustomerName'       => $data['customer_name'],
                'DisplayCurrencyIso' => 'EGP',
                'MobileCountryCode'  => '20',
                'CustomerMobile'     => $data['customer_phone'],
                'CustomerEmail'      => $data['customer_email'],
                'CallBackUrl'        => config('myfatoorah.success_url'),
                'ErrorUrl'           => config('myfatoorah.failure_url'),
                'Language'           => 'ar',
                'CustomerReference'  => $data['reference_number'],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])->post($this->baseUrl . '/v2/SendPayment', $payload);

            if ($response->successful()) {
                $result = $response->json();
                if ($result['IsSuccess']) {
                    return [
                        'success' => true,
                        'payment_url' => $result['Data']['InvoiceURL'],
                        'invoice_id' => $result['Data']['InvoiceId'],
                    ];
                }
            }

            Log::error('MyFatoorah SendPayment Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'فشل في إنشاء عملية الدفع',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('MyFatoorah Exception', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء الاتصال ببوابة الدفع'
            ];
        }
    }

    /**
     * Get Payment Status
     */
    public function getPaymentStatus($paymentId)
    {
        try {
            $payload = [
                'Key'     => $paymentId,
                'KeyType' => 'PaymentId'
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])->post($this->baseUrl . '/v2/GetPaymentStatus', $payload);

            if ($response->successful()) {
                $result = $response->json();
                if ($result['IsSuccess']) {
                    return [
                        'success' => true,
                        'data' => $result['Data'],
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'فشل في جلب حالة الدفع'
            ];

        } catch (\Exception $e) {
            Log::error('MyFatoorah Status Exception', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب حالة الدفع'
            ];
        }
    }
}
