<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MyFatoorah API Configuration
    |--------------------------------------------------------------------------
    |
    | إعدادات بوابة الدفع ماي فاتورة
    |
    */

    'api_key' => env('MYFATOORAH_API_KEY'),
    'base_url' => env('MYFATOORAH_BASE_URL', 'https://apitest.myfatoorah.com'), // Default to test
    'test_mode' => env('MYFATOORAH_TEST_MODE', true),
    
    // Redirect URLs
    'success_url' => env('MYFATOORAH_SUCCESS_URL', env('APP_URL') . '/api/payment/myfatoorah/callback?status=success'),
    'failure_url' => env('MYFATOORAH_FAILURE_URL', env('APP_URL') . '/api/payment/myfatoorah/callback?status=fail'),
    
    // Webhook URL
    'webhook_url' => env('MYFATOORAH_WEBHOOK_URL', env('APP_URL') . '/api/payment/myfatoorah/webhook'),
];
