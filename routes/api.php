<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HobbyController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\PostController;

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
});


// Admin routes (require authentication)
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::post('/hobbies', [HobbyController::class, 'store']); // Create hobby
    Route::post('/hobbies/{id}', [HobbyController::class, 'update']); // Update hobby (POST for file upload)
    Route::delete('/hobbies/{id}', [HobbyController::class, 'destroy']); // Delete hobby
});

