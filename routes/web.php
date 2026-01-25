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
