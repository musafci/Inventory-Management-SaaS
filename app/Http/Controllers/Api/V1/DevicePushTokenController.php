<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Device\DeleteDevicePushTokenRequest;
use App\Http\Requests\Device\StoreDevicePushTokenRequest;
use App\Http\Resources\DevicePushTokenResource;
use App\Services\DevicePushTokenService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

#[Group('Devices', description: 'Mobile device push token registration.', weight: 90)]
class DevicePushTokenController extends ApiController
{
    public function __construct(
        protected DevicePushTokenService $devicePushTokenService,
    ) {}

    public function store(StoreDevicePushTokenRequest $request): JsonResponse
    {
        $token = $this->devicePushTokenService->register(
            $request->user(),
            $request->validated(),
        );

        return $this->success(new DevicePushTokenResource($token), status: 201);
    }

    public function destroy(DeleteDevicePushTokenRequest $request): Response
    {
        $this->devicePushTokenService->unregister(
            $request->user(),
            $request->validated('expo_push_token'),
        );

        return response()->noContent();
    }
}
