<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductAuthorizationProbeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('refresh', [AuthController::class, 'refresh']);

        Route::middleware('auth:api')->group(function (): void {
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware(['auth:api', 'tenant'])->group(function (): void {
        Route::post('products/authorization-probe', [ProductAuthorizationProbeController::class, 'store'])
            ->middleware('permission:products.create,api');
    });
});
