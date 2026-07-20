<?php

namespace App\Http\Controllers\Api\Platform\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Platform\PlatformLoginRequest;
use App\Http\Resources\PlatformAdminResource;
use App\Services\PlatformAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlatformAuthController extends ApiController
{
    public function __construct(
        protected PlatformAuthService $platformAuthService,
    ) {}

    public function login(PlatformLoginRequest $request): JsonResponse
    {
        $result = $this->platformAuthService->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        return $this->success([
            'admin' => new PlatformAdminResource($result['admin']),
            'token' => $result['token'],
        ]);
    }

    public function logout(Request $request): Response
    {
        $this->platformAuthService->logout($request->user('platform'));

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success([
            'admin' => new PlatformAdminResource($request->user('platform')),
        ]);
    }
}
