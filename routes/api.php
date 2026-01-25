<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HobbyController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\FawaterakWebhookController;
use Illuminate\Support\Facades\Broadcast;

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
Route::post('/login', [AuthController::class, 'login'])->name('login'); // Send OTP to phone
Route::post('/verify', [AuthController::class, 'verify']); // Verify OTP code

// Public hobby routes
Route::get('/hobbies', [HobbyController::class, 'index']); // List all hobbies

// Protected routes (require authentication)
Route::middleware('auth:api')->group(function () {
    // Profile management
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']); // Logout
    Route::post('/refresh', function () {
        return response()->json([
            'success' => true,
            'token' => auth('api')->refresh(),
        ]);
    }); // Refresh JWT Token


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

    // Notifications
    Route::post('/notifications/update-token', [\App\Http\Controllers\Api\NotificationController::class, 'updateToken']);
    Route::post('/notifications/send-test', [\App\Http\Controllers\Api\NotificationController::class, 'sendTest']);
    
    // ========== Packages & Wallet Routes ==========
    
    // Packages
    Route::get('/packages', [PackageController::class, 'index']); // Get all active packages
    Route::get('/packages/{id}', [PackageController::class, 'show']); // Get single package

    // Wallet
    Route::prefix('wallet')->group(function () {
        Route::get('/balance', [WalletController::class, 'getBalance']); // Get wallet balance and gems
        Route::get('/transactions', [WalletController::class, 'getTransactions']); // Get transaction history
        Route::get('/payment-methods', [WalletController::class, 'paymentMethods']); // Get available payment methods
        Route::post('/deposit', [WalletController::class, 'initiateDeposit']); // Charge wallet (initiate payment)
        Route::post('/withdrawal', [WalletController::class, 'initiateWithdrawal']); // Withdraw from wallet
        Route::post('/purchase-package', [WalletController::class, 'purchasePackage']); // Purchase package with wallet balance
    });

    // ========== Chat Routes (Real-time with Pusher) ==========
    Route::prefix('chat')->group(function () {
        Route::get('/conversations', [\App\Http\Controllers\Api\ChatController::class, 'getConversations']); // Get all conversations
        Route::post('/conversations/start', [\App\Http\Controllers\Api\ChatController::class, 'startConversation']); // Start/get conversation with user
        Route::get('/conversations/{conversationId}/messages', [\App\Http\Controllers\Api\ChatController::class, 'getMessages']); // Get messages
        Route::post('/conversations/{conversationId}/messages', [\App\Http\Controllers\Api\ChatController::class, 'sendMessage']); // Send message (text or image)
    });

    // Manual Broadcasting Auth Route (Final Fix with Logging)
    Route::post('/broadcasting/auth', function (Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Log::info('=== PUSHER AUTH REQUEST START ===');
        \Illuminate\Support\Facades\Log::info('Headers: ', $request->headers->all());
        \Illuminate\Support\Facades\Log::info('Input: ', $request->all());
        
        $user = auth('api')->user();
        
        if (!$user) {
            \Illuminate\Support\Facades\Log::error('Pusher Auth Failed: User not authenticated via JWT');
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        \Illuminate\Support\Facades\Log::info('Pusher Auth Success for User ID: ' . $user->id);
        
        try {
            // Manual Pusher Signature Generation to bypass config issues
            $pusher = new \Pusher\Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                config('broadcasting.connections.pusher.options')
            );
            
            $socketId = $request->input('socket_id');
            $channelName = $request->input('channel_name');
            
            // Generate the auth string manually
            $auth = $pusher->authorizeChannel($channelName, $socketId);
            
            \Illuminate\Support\Facades\Log::info('Pusher Manual Auth Success');
            
            return response()->json(json_decode($auth, true));
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('BROADCAST MANUAL FAILURE: ' . $e->getMessage());
            return response()->json(['error' => 'Broadcasting authentication failed'], 500);
        }
    });
});


// Admin routes (require authentication)
Route::middleware('auth:api')->prefix('admin')->group(function () {
    Route::post('/hobbies', [HobbyController::class, 'store']); // Create hobby
    Route::post('/hobbies/{id}', [HobbyController::class, 'update']); // Update hobby (POST for file upload)
    Route::delete('/hobbies/{id}', [HobbyController::class, 'destroy']); // Delete hobby
});

// ========== Fawaterak Payment Webhooks (Public - No Auth) ==========
Route::prefix('fawaterak')->group(function () {
    Route::post('/webhook', [FawaterakWebhookController::class, 'webhook']); // Payment webhook
    Route::get('/callback', [FawaterakWebhookController::class, 'callback']); // Payment callback (redirect)
    Route::post('/callback', [FawaterakWebhookController::class, 'callback']); // Payment callback (POST)
    Route::get('/test-callback', [FawaterakWebhookController::class, 'testCallback']); // Test payment callback
});

