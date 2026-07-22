<?php

use App\Http\Controllers\Api\Platform\V1\PlatformActivityLogController;
use App\Http\Controllers\Api\Platform\V1\PlatformAdminController;
use App\Http\Controllers\Api\Platform\V1\PlatformAuthController;
use App\Http\Controllers\Api\Platform\V1\PlatformFeatureFlagController;
use App\Http\Controllers\Api\Platform\V1\PlatformImpersonationController;
use App\Http\Controllers\Api\Platform\V1\PlatformOrganizationController;
use App\Http\Controllers\Api\Platform\V1\PlatformOrganizationSubscriptionController;
use App\Http\Controllers\Api\Platform\V1\PlatformPlanController;
use App\Http\Controllers\Api\Platform\V1\PlatformSupportNoteController;
use Illuminate\Support\Facades\Route;

Route::prefix('platform/v1')->group(function (): void {
    Route::post('auth/login', [PlatformAuthController::class, 'login']);

    Route::middleware(['auth:platform', 'throttle:api-tenant'])->group(function (): void {
        Route::post('auth/logout', [PlatformAuthController::class, 'logout']);
        Route::get('auth/me', [PlatformAuthController::class, 'me']);

        Route::get('plans', [PlatformPlanController::class, 'index']);

        Route::get('activity-logs/summary', [PlatformActivityLogController::class, 'summary']);
        Route::get('activity-logs', [PlatformActivityLogController::class, 'index']);

        Route::get('platform-admins', [PlatformAdminController::class, 'index']);
        Route::post('platform-admins', [PlatformAdminController::class, 'store']);
        Route::delete('platform-admins/{adminId}', [PlatformAdminController::class, 'destroy']);

        Route::get('organizations', [PlatformOrganizationController::class, 'index']);
        Route::get('organizations/{organizationId}', [PlatformOrganizationController::class, 'show']);
        Route::patch('organizations/{organizationId}', [PlatformOrganizationController::class, 'update']);
        Route::get('organizations/{organizationId}/subscription', [PlatformOrganizationSubscriptionController::class, 'show']);
        Route::patch('organizations/{organizationId}/subscription', [PlatformOrganizationSubscriptionController::class, 'update']);

        Route::get('organizations/{organizationId}/support-notes', [PlatformSupportNoteController::class, 'index']);
        Route::post('organizations/{organizationId}/support-notes', [PlatformSupportNoteController::class, 'store']);

        Route::get('organizations/{organizationId}/activity-logs', [PlatformActivityLogController::class, 'indexForOrganization']);

        Route::get('organizations/{organizationId}/feature-flags', [PlatformFeatureFlagController::class, 'index']);
        Route::patch('organizations/{organizationId}/feature-flags/{featureFlagId}', [PlatformFeatureFlagController::class, 'update']);

        Route::post('organizations/{organizationId}/impersonate', [PlatformImpersonationController::class, 'start']);
        Route::post('impersonation/end', [PlatformImpersonationController::class, 'end']);
    });
});
