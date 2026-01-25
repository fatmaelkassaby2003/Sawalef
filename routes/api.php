<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HobbyController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\FawaterakWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public authentication routes
Route::post('/register', [AuthController::class, 'register']); // Register new account
Route::post('/login', [AuthController::class, 'login']); // Send OTP to phone
Route::post('/verify', [AuthController::class, 'verify']); // Verify OTP code

// Public hobby routes
Route::get('/hobbies', [HobbyController::class, 'index']); // List all hobbies

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Profile management
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']); // Logout

    // User hobbies
    Route::get('/my-hobbies', [HobbyController::class, 'myHobbies']);
    Route::post('/my-hobbies', [HobbyController::class, 'updateMyHobbies']);

    // Matching
    Route::get('/similar-users', [MatchController::class, 'getSimilarUsers']);
    Route::post('/search-by-country', [MatchController::class, 'searchByCountry']); // Search random user by country with 80% match
    Route::post('/advanced-search', [MatchController::class, 'advancedSearch']); // Advanced search with filters

    // Posts & Social
    Route::get('/posts', [PostController::class, 'index']); // Feed
    Route::get('/my-posts', [PostController::class, 'myPosts']); // My Posts
    Route::post('/posts', [PostController::class, 'store']); // Create post
    Route::get('/posts/{id}', [PostController::class, 'show']); // Show post
    Route::post('/posts/{id}', [PostController::class, 'update']); // Update post (POST for file upload)
    Route::delete('/posts/{id}', [PostController::class, 'destroy']); // Delete post
    Route::post('/posts/{id}/like', [PostController::class, 'like']); // Toggle like
    Route::post('/posts/{id}/comment', [PostController::class, 'comment']); // Add comment

    // User Profile (Public/Protected view)
    Route::get('/users/{id}/profile', [PostController::class, 'userProfile']); // View user profile with posts

    // ========== Packages & Wallet Routes ==========
    
    // Packages
    Route::get('/packages', [PackageController::class, 'index']); // Get all active packages
    Route::get('/packages/{id}', [PackageController::class, 'show']); // Get single package

    // Wallet
    Route::prefix('wallet')->group(function () {
        Route::get('/balance', [WalletController::class, 'getBalance']); // Get wallet balance and gems
        Route::get('/transactions', [WalletController::class, 'getTransactions']); // Get transaction history
        Route::post('/deposit', [WalletController::class, 'initiateDeposit']); // Charge wallet (initiate payment)
        Route::post('/withdrawal', [WalletController::class, 'initiateWithdrawal']); // Withdraw from wallet
        Route::post('/purchase-package', [WalletController::class, 'purchasePackage']); // Purchase package with wallet balance
    });
});


// Admin routes (require authentication)
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::post('/hobbies', [HobbyController::class, 'store']); // Create hobby
    Route::post('/hobbies/{id}', [HobbyController::class, 'update']); // Update hobby (POST for file upload)
    Route::delete('/hobbies/{id}', [HobbyController::class, 'destroy']); // Delete hobby
});

// ========== Fawaterak Payment Webhooks (Public - No Auth) ==========
Route::prefix('fawaterak')->group(function () {
    Route::post('/webhook', [FawaterakWebhookController::class, 'webhook']); // Payment webhook
    Route::get('/callback', [FawaterakWebhookController::class, 'callback']); // Payment callback (redirect)
    Route::post('/callback', [FawaterakWebhookController::class, 'callback']); // Payment callback (POST)
});

