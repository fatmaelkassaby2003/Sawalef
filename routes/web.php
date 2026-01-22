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

Route::get('/', function () {
    $dashboardPath = public_path('index.html');
    
    if (file_exists($dashboardPath)) {
        return response()->file($dashboardPath);
    }
    
    return response('Dashboard not found', 404);
});
