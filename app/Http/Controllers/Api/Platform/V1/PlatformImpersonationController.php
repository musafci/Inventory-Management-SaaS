<?php

namespace App\Http\Controllers\Api\Platform\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Platform\StartImpersonationRequest;
use App\Http\Resources\ImpersonationLogResource;
use App\Http\Resources\UserResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PlatformImpersonationController extends ApiController
{
    public function __construct(
        protected ImpersonationService $impersonationService,
    ) {}

    public function start(StartImpersonationRequest $request, int $organizationId): JsonResponse
    {
        $organization = Organization::query()->findOrFail($organizationId);
        $user = User::query()->findOrFail($request->validated('user_id'));

        $result = $this->impersonationService->start(
            $request->user('platform'),
            $organization,
            $user,
            $request->validated('reason'),
        );

        return $this->success([
            'impersonation' => $result['impersonation'],
            'log' => new ImpersonationLogResource($result['log']),
            'user' => new UserResource($user),
            'organization_id' => $result['organization_id'],
            'token' => $result['token'],
        ], status: Response::HTTP_CREATED);
    }

    public function end(): JsonResponse
    {
        $admin = auth('platform')->user();
        $log = $this->impersonationService->end($admin);

        return $this->success([
            'ended' => $log !== null,
            'log' => $log ? new ImpersonationLogResource($log) : null,
        ]);
    }
}
