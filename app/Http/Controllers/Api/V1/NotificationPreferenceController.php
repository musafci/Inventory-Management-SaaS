<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Notification\UpdateNotificationPreferencesRequest;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends ApiController
{
    public function __construct(
        protected NotificationPreferenceService $preferenceService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\Organization $organization */
        $organization = app('currentOrganization');

        return $this->success([
            'events' => $this->preferenceService->eventKeys(),
            'preferences' => $this->preferenceService->preferencesFor($request->user(), $organization->id),
        ]);
    }

    public function update(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        /** @var \App\Models\Organization $organization */
        $organization = app('currentOrganization');

        $preferences = $this->preferenceService->updateFor(
            $request->user(),
            $organization->id,
            $request->validated('preferences'),
        );

        return $this->success([
            'events' => $this->preferenceService->eventKeys(),
            'preferences' => $preferences,
        ]);
    }
}
