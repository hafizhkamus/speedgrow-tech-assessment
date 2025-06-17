<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;

Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::post('/transactions', [TransactionController::class, 'processTransaction']);
        Route::get('/transactions', [TransactionController::class, 'getTransactions']);
        Route::get('/transactions/stats', [TransactionController::class, 'getTransactionStats']);
        Route::get('/transactions/history', [TransactionController::class, 'history']);
        Route::get('/transactions/{id}', [TransactionController::class, 'getTransaction']);

        Route::middleware('throttle:10,1')->post('/transactions/nfc', [NfcTransactionController::class, 'process']);
    });
});
