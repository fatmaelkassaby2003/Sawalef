<?php

return [
    'api_key'         => env('MOYASAR_API_KEY'),
    'publishable_key' => env('MOYASAR_PUBLISHABLE_KEY'),
    'base_url'        => env('MOYASAR_BASE_URL', 'https://api.moyasar.com/v1'),
    'test_mode'       => env('MOYASAR_TEST_MODE', true),
    'currency'        => env('MOYASAR_CURRENCY', 'SAR'),
    'instant_success' => env('MOYASAR_INSTANT_SUCCESS', false), // Set true for debug/testing

    // Callback URL (sent to Moyasar, user is redirected here after payment)
    'success_url' => env('MOYASAR_SUCCESS_URL', env('APP_URL') . '/api/moyasar/callback'),

    // Webhook URL (server-to-server notification)
    'webhook_url' => env('APP_URL') . '/api/moyasar/webhook',
];
