<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HobbyController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\FawaterakWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// -----------------------------------------------------------------------------
// BROADCASTING AUTHENTICATION (Manual & Explicit) - RESTORED ✅
// -----------------------------------------------------------------------------
Route::match(['get', 'post', 'head'], '/broadcasting/auth', function (Request $request) {
    
    // 1. GET/HEAD: Health Check
    if ($request->isMethod('get') || $request->isMethod('head')) {
        $config = config('broadcasting.connections.pusher');
        $displaySecret = substr($config['secret'] ?? 'unknown', 0, 5) . '******'; 

        $status = [
            'status' => 'ready',
            'message' => 'Pusher Auth Endpoint is Active (Manual Route) ✅',
            'methods_allowed' => ['GET', 'POST', 'HEAD'],
            'pusher_config' => [
                'app_id' => $config['app_id'] ?? 'null',
                'key' => $config['key'] ?? 'null',
                'secret' => $displaySecret,
                'cluster' => $config['options']['cluster'] ?? 'mt1',
            ],
            'server_time' => now()->toDateTimeString(),
        ];
        return response()->json($status);
    }

    // 2. POST: Real Authentication
    $user = $request->user();
    
    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    \Illuminate\Support\Facades\Log::info('Pusher Auth Request', [
        'user_id' => $user->id,
        'socket_id' => $request->socket_id,
        'channel_name' => $request->channel_name
    ]);

    if (!$request->socket_id || !$request->channel_name) {
        return response()->json(['message' => 'Missing socket_id or channel_name'], 400);
    }

    try {
        $pusher = new \Pusher\Pusher(
            config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'),
            config('broadcasting.connections.pusher.app_id'),
            ['cluster' => config('broadcasting.connections.pusher.options.cluster'), 'useTLS' => true]
        );

        $auth = $pusher->authorizeChannel($request->channel_name, $request->socket_id, json_encode([
            'user_id' => (string) $user->id,
            'user_info' => [
                'name' => $user->name,
                'avatar' => $user->profile_image ? url('storage/' . $user->profile_image) : null,
            ]
        ]));
        
        return response($auth);
        
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Pusher Auth Failed: ' . $e->getMessage());
        return response()->json(['message' => 'Auth Error'], 403);
    }
})->middleware('auth:api');


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
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']); // Get All
    Route::get('/notifications/latest', [\App\Http\Controllers\Api\NotificationController::class, 'latest']); // Get Latest 5
    Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']); // Get Unread Count
    Route::post('/notifications/mark-as-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']); // Mark specific as read
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']); // Mark all as read
    
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

    // ========== Friendship Routes ==========
    Route::prefix('friends')->group(function () {
        Route::post('/request', [\App\Http\Controllers\Api\FriendshipController::class, 'sendRequest']); // Send friend request
        Route::post('/accept', [\App\Http\Controllers\Api\FriendshipController::class, 'acceptRequest']); // Accept friend request
        Route::post('/decline', [\App\Http\Controllers\Api\FriendshipController::class, 'declineRequest']); // Decline friend request
        Route::get('/pending', [\App\Http\Controllers\Api\FriendshipController::class, 'getPendingRequests']); // Get pending requests
        Route::get('/list', [\App\Http\Controllers\Api\FriendshipController::class, 'getFriends']); // Get list of friends
    });

    // ========== Agora Call Routes ==========
    Route::prefix('calls')->group(function () {
        Route::post('/start', [\App\Http\Controllers\Api\CallController::class, 'startCall']); // Initiate call
        Route::post('/accept', [\App\Http\Controllers\Api\CallController::class, 'acceptCall']); // Accept call (get token)
        Route::post('/end', [\App\Http\Controllers\Api\CallController::class, 'endCall']); // End/Decline call
        Route::get('/history', [\App\Http\Controllers\Api\CallController::class, 'getHistory']); // Get call logs
    });

    // ========== Chat Routes (Real-time with Pusher) ==========
    Route::prefix('chat')->group(function () {
        Route::get('/conversations', [\App\Http\Controllers\Api\ChatController::class, 'getConversations']); // Get all conversations
        Route::post('/conversations/start', [\App\Http\Controllers\Api\ChatController::class, 'startConversation']); // Start/get conversation with user
        Route::get('/conversations/{conversationId}/messages', [\App\Http\Controllers\Api\ChatController::class, 'getMessages']); // Get messages
        Route::post('/conversations/{conversationId}/messages', [\App\Http\Controllers\Api\ChatController::class, 'sendMessage']); // Send message (text or image)
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
