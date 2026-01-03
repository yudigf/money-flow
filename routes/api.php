<?php

declare(strict_types=1);

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| API Routes - MoneyFlow
|--------------------------------------------------------------------------
|
| All routes below require authentication via Sanctum.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    // Wallet routes
    Route::apiResource('wallets', WalletController::class);

    // Category routes
    Route::apiResource('categories', CategoryController::class);

    // Transaction routes
    Route::apiResource('transactions', TransactionController::class);
});
