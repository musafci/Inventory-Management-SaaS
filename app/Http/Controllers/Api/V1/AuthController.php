<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\AuthTokenResource;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\ImpersonationService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Auth', description: 'Registration, login, token refresh, and current user profile.', weight: 1)]
class AuthController extends ApiController
{
    public function __construct(
        protected AuthService $authService,
        protected ImpersonationService $impersonationService,
    ) {}

    #[Endpoint(operationId: 'auth.register', title: 'Register organization and owner', description: 'Creates a new organization, owner user, and returns Passport OAuth tokens.')]
    #[Response(
        status: 201,
        description: 'Organization and owner user created with OAuth tokens.',
        examples: [[
            'data' => [
                'user' => [
                    'id' => 1,
                    'name' => 'Jane Owner',
                    'email' => 'jane@acme.test',
                    'phone' => '+15551234567',
                    'status' => 'active',
                    'default_organization_id' => 1,
                    'last_login_at' => null,
                ],
                'organizations' => [[
                    'id' => 1,
                    'name' => 'Acme Inventory',
                    'slug' => 'acme-inventory',
                    'email' => null,
                    'phone' => null,
                    'plan' => 'growth',
                    'status' => 'active',
                    'trial_ends_at' => '2026-08-10T00:00:00.000000Z',
                    'role' => 'Org Owner',
                ]],
                'token' => [
                    'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...',
                    'refresh_token' => 'def50200a1b2c3d4...',
                    'expires_in' => 31536000,
                    'token_type' => 'Bearer',
                ],
            ],
        ]],
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->success([
            'user' => new UserResource($result['user']),
            'organizations' => OrganizationResource::collection($result['organizations']),
            'token' => new AuthTokenResource($result['token']),
        ], status: 201);
    }

    #[Endpoint(operationId: 'auth.login', title: 'Login', description: 'Authenticates a user and returns OAuth tokens with their organizations.')]
    #[Response(
        status: 200,
        description: 'Authenticated successfully.',
        examples: [[
            'data' => [
                'user' => [
                    'id' => 1,
                    'name' => 'Jane Owner',
                    'email' => 'jane@acme.test',
                    'phone' => '+15551234567',
                    'status' => 'active',
                    'default_organization_id' => 1,
                    'last_login_at' => '2026-07-10T12:00:00.000000Z',
                ],
                'organizations' => [[
                    'id' => 1,
                    'name' => 'Acme Inventory',
                    'slug' => 'acme-inventory',
                    'email' => null,
                    'phone' => null,
                    'plan' => 'growth',
                    'status' => 'active',
                    'trial_ends_at' => '2026-08-10T00:00:00.000000Z',
                    'role' => 'Org Owner',
                ]],
                'token' => [
                    'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...',
                    'refresh_token' => 'def50200a1b2c3d4...',
                    'expires_in' => 31536000,
                    'token_type' => 'Bearer',
                ],
            ],
        ]],
    )]
    #[Response(
        status: 401,
        description: 'Invalid credentials.',
        examples: [[
            'message' => 'The user credentials were incorrect.',
            'errors' => [],
        ]],
    )]
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

    #[Endpoint(operationId: 'auth.refresh', title: 'Refresh access token', description: 'Exchanges a refresh token for a new access token.')]
    #[Response(
        status: 200,
        description: 'Token refreshed successfully.',
        examples: [[
            'data' => [
                'user' => [
                    'id' => 1,
                    'name' => 'Jane Owner',
                    'email' => 'jane@acme.test',
                    'phone' => '+15551234567',
                    'status' => 'active',
                    'default_organization_id' => 1,
                    'last_login_at' => '2026-07-10T12:00:00.000000Z',
                ],
                'organizations' => [[
                    'id' => 1,
                    'name' => 'Acme Inventory',
                    'slug' => 'acme-inventory',
                    'email' => null,
                    'phone' => null,
                    'plan' => 'growth',
                    'status' => 'active',
                    'trial_ends_at' => '2026-08-10T00:00:00.000000Z',
                    'role' => 'Org Owner',
                ]],
                'token' => [
                    'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...',
                    'refresh_token' => 'def50200f6e5d4c3...',
                    'expires_in' => 31536000,
                    'token_type' => 'Bearer',
                ],
            ],
        ]],
    )]
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $result = $this->authService->refresh($request->validated('refresh_token'));

        return $this->success([
            'user' => new UserResource($result['user']),
            'organizations' => OrganizationResource::collection($result['organizations']),
            'token' => new AuthTokenResource($result['token']),
        ]);
    }

    #[Endpoint(operationId: 'auth.me', title: 'Current user profile', description: 'Returns the authenticated user and organizations they belong to.')]
    #[Response(
        status: 200,
        description: 'Current user profile and organizations.',
        examples: [[
            'data' => [
                'user' => [
                    'id' => 1,
                    'name' => 'Jane Owner',
                    'email' => 'jane@acme.test',
                    'phone' => '+15551234567',
                    'status' => 'active',
                    'default_organization_id' => 1,
                    'last_login_at' => '2026-07-10T12:00:00.000000Z',
                ],
                'organizations' => [[
                    'id' => 1,
                    'name' => 'Acme Inventory',
                    'slug' => 'acme-inventory',
                    'email' => null,
                    'phone' => null,
                    'plan' => 'growth',
                    'status' => 'active',
                    'trial_ends_at' => '2026-08-10T00:00:00.000000Z',
                    'role' => 'Org Owner',
                ]],
            ],
        ]],
    )]
    public function me(Request $request): JsonResponse
    {
        $result = $this->authService->me($request->user());

        return $this->success([
            'user' => new UserResource($result['user']),
            'organizations' => OrganizationResource::collection($result['organizations']),
            'impersonation' => $this->impersonationService->activeSessionForUser(
                $request->user(),
                $request->bearerToken(),
            ),
        ]);
    }

    public function logout(Request $request): \Illuminate\Http\Response
    {
        if ($request->user() !== null && $request->bearerToken() !== null) {
            $this->authService->logout($request->user(), $request->bearerToken());
        }

        return response()->noContent();
    }
}
