<?php

use App\Http\Controllers\Api\Platform\V1\PlatformAuthController;
use App\Http\Controllers\Api\Platform\V1\PlatformOrganizationController;
use Illuminate\Support\Facades\Route;

Route::prefix('platform/v1')->group(function (): void {
    Route::post('auth/login', [PlatformAuthController::class, 'login']);

    Route::middleware(['auth:platform', 'throttle:api-tenant'])->group(function (): void {
        Route::post('auth/logout', [PlatformAuthController::class, 'logout']);
        Route::get('auth/me', [PlatformAuthController::class, 'me']);

        Route::get('organizations', [PlatformOrganizationController::class, 'index']);
        Route::get('organizations/{organizationId}', [PlatformOrganizationController::class, 'show']);
        Route::patch('organizations/{organizationId}', [PlatformOrganizationController::class, 'update']);
    });
});
