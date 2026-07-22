<?php

namespace App\Http\Controllers\Api\Platform\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;

class PlatformPlanController extends ApiController
{
    public function index(): JsonResponse
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        return $this->success(PlanResource::collection($plans));
    }
}
