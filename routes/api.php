<?php

use App\Models\TenantScopeStub;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('tenant-scope-probe', function () {
        return response()->json([
            'data' => TenantScopeStub::query()->get(['id', 'organization_id', 'label']),
        ]);
    })->middleware(['auth:api']);
});
