<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\ApiTokenController;
use App\Http\Controllers\Api\Payment\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API専用のルートを登録します。
| routes/api.phpのルートには、自動的に/apiが付きます。
|
*/

/*
|--------------------------------------------------------------------------
| API Authentication
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function (): void {
    Route::post('/token', [ApiTokenController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('api.auth.token.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated API
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->name('api.user.show');

    Route::post('/payments', [PaymentController::class, 'store'])
        ->middleware('abilities:payments:create')
        ->name('api.payments.store');

    Route::post('/payments/{payment}/confirm', [PaymentController::class, 'confirm'])
        ->middleware('abilities:payments:confirm')
        ->name('api.payments.confirm');
});
