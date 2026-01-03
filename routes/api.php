<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - MoneyFlow
|--------------------------------------------------------------------------
|
| Public routes for authentication.
| Protected routes require Sanctum token.
|
*/

// Public routes (no authentication required)
Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function (): void {
    // Auth routes
    Route::prefix('auth')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/user', [AuthController::class, 'user'])->name('auth.user');
    });

    // Wallet routes
    Route::apiResource('wallets', WalletController::class);

    // Category routes
    Route::apiResource('categories', CategoryController::class);

    // Transaction routes
    Route::apiResource('transactions', TransactionController::class);
});
