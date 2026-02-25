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
        // Cleaning API Key from spaces, quotes, and newlines
        $key = config('myfatoorah.api_key');
        $this->apiKey = trim(str_replace(["\n", "\r", '"', "'", " "], '', (string)$key));
        $this->baseUrl = rtrim(config('myfatoorah.base_url'), '/');

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
                'NotificationOption' => 'LNK',
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

            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->post($this->baseUrl . '/v2/SendPayment', $payload);

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
                'error' => $response->json() ?: $response->body()
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
     * Initiate Payment - Get available payment methods
     */
    public function initiatePayment($amount)
    {
        try {
            $payload = [
                'InvoiceAmount' => $amount,
                'CurrencyIso'  => 'EGP',
            ];

            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->post($this->baseUrl . '/v2/InitiatePayment', $payload);

            if ($response->successful()) {
                $result = $response->json();
                if ($result['IsSuccess']) {
                    return [
                        'success' => true,
                        'data' => $result['Data']['PaymentMethods'],
                    ];
                }
            }

            Log::error('MyFatoorah InitiatePayment Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'فشل في جلب طرق الدفع',
                'error' => $response->json() ?: $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('MyFatoorah Initiate Exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'حدث خطأ أثناء جلب طرق الدفع'];
        }
    }

    /**
     * Execute Payment - Direct payment for a specific method
     */
    public function executePayment($data)
    {
        try {
            $payload = [
                'PaymentMethodId'    => $data['payment_method_id'],
                'CustomerName'       => $data['customer_name'],
                'DisplayCurrencyIso' => 'EGP',
                'MobileCountryCode'  => '20',
                'CustomerMobile'     => $data['customer_phone'],
                'CustomerEmail'      => $data['customer_email'],
                'InvoiceValue'       => $data['amount'],
                'CallBackUrl'        => config('myfatoorah.success_url'),
                'ErrorUrl'           => config('myfatoorah.failure_url'),
                'Language'           => 'ar',
                'CustomerReference'  => $data['reference_number'],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'Mozilla/5.0 SawalefAPI/1.0',
            ])->post($this->baseUrl . '/v2/ExecutePayment', $payload);

            if ($response->successful()) {
                $result = $response->json();
                if ($result['IsSuccess']) {
                    return [
                        'success' => true,
                        'payment_url' => $result['Data']['PaymentURL'],
                        'invoice_id' => $result['Data']['InvoiceId'],
                    ];
                }
            }

            Log::error('MyFatoorah Final Error Check', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers_sent' => 'Bearer ' . substr($this->apiKey, 0, 5) . '...'
            ]);

            return [
                'success' => false,
                'message' => 'فشل في تنفيذ عملية الدفع',
                'error' => $response->json() ?: $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('MyFatoorah Exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'حدث خطأ أثناء تنفيذ الدفع'];
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
