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

    // Budget routes
    Route::get('budgets/summary', [\App\Http\Controllers\BudgetController::class, 'summary'])->name('budgets.summary');
    Route::apiResource('budgets', \App\Http\Controllers\BudgetController::class)->except(['show']);

    // Report routes
    Route::prefix('reports')->group(function (): void {
        Route::get('/overview', [\App\Http\Controllers\ReportController::class, 'overview'])->name('reports.overview');
        Route::get('/monthly-trend', [\App\Http\Controllers\ReportController::class, 'monthlyTrend'])->name('reports.monthly-trend');
        Route::get('/category-breakdown', [\App\Http\Controllers\ReportController::class, 'categoryBreakdown'])->name('reports.category-breakdown');
        Route::get('/daily-breakdown', [\App\Http\Controllers\ReportController::class, 'dailyBreakdown'])->name('reports.daily-breakdown');
    });

    // Bill routes
    Route::get('bills/summary', [\App\Http\Controllers\BillController::class, 'summary'])->name('bills.summary');
    Route::post('bills/{bill}/mark-paid', [\App\Http\Controllers\BillController::class, 'markPaid'])->name('bills.mark-paid');
    Route::apiResource('bills', \App\Http\Controllers\BillController::class);

    // Investment routes
    Route::get('investments/summary', [\App\Http\Controllers\InvestmentController::class, 'summary'])->name('investments.summary');
    Route::patch('investments/{investment}/price', [\App\Http\Controllers\InvestmentController::class, 'updatePrice'])->name('investments.update-price');
    Route::apiResource('investments', \App\Http\Controllers\InvestmentController::class);
});
