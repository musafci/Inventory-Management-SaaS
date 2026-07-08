<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

class ProductAuthorizationProbeController extends ApiController
{
    /**
     * Probe endpoint used to verify permission middleware behavior.
     */
    public function store(): JsonResponse
    {
        return $this->success(['created' => true], status: 201);
    }
}
