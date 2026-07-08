<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\AuthTokenResource;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->success([
            'user' => new UserResource($result['user']),
            'organizations' => OrganizationResource::collection($result['organizations']),
            'token' => new AuthTokenResource($result['token']),
        ], status: 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        return $this->success([
            'user' => new UserResource($result['user']),
            'organizations' => OrganizationResource::collection($result['organizations']),
            'token' => new AuthTokenResource($result['token']),
        ]);
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $result = $this->authService->refresh($request->validated('refresh_token'));

        return $this->success([
            'user' => new UserResource($result['user']),
            'organizations' => OrganizationResource::collection($result['organizations']),
            'token' => new AuthTokenResource($result['token']),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $result = $this->authService->me($request->user());

        return $this->success([
            'user' => new UserResource($result['user']),
            'organizations' => OrganizationResource::collection($result['organizations']),
        ]);
    }
}
