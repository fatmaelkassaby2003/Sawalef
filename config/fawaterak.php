<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fawaterak API Configuration
    |--------------------------------------------------------------------------
    | 
    | إعدادات بوابة الدفع فواتيرك
    |
    */

    'api_key' => env('FAWATERAK_API_KEY'),
    'base_url' => env('FAWATERAK_BASE_URL', 'https://app.fawaterk.com/api/v2'),
    
    // Webhook & Callback URLs
    'webhook_url' => env('FAWATERAK_WEBHOOK_URL'),
    'callback_url' => env('FAWATERAK_CALLBACK_URL'),
    
    // Redirect URLs
    'success_url' => env('FAWATERAK_SUCCESS_URL'),
    'failure_url' => env('FAWATERAK_FAILURE_URL'),
    
    // Payment Methods
    'payment_methods' => [
        'card' => 1,        // Visa/MasterCard
        'vodafone' => 2,    // Vodafone Cash
        'meeza' => 4,       // Meeza
        'fawry' => 5,       // Fawry
    ],
];
