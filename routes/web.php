<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', '/admin');

// Test Payment Page (for development/testing)
Route::get('/payment/test', function () {
    $transactionId = request('transaction_id');
    $amount = request('amount');
    
    return view('payment.test', compact('transactionId', 'amount'));
})->name('payment.test');

// Test Callback Route (Web fallback)
Route::get('/payment/test-callback', [\App\Http\Controllers\Api\FawaterakWebhookController::class, 'testCallback'])->name('payment.test-callback');

// Fix for Controller Redirects (Redirect /payment/success to /payment/success.php)
Route::get('/payment/success', function () {
    return redirect('/payment/success.php?' . http_build_query(request()->all()));
});

Route::get('/payment/failed', function () {
    return redirect('/payment/failed.php?' . http_build_query(request()->all()));
});
