<?php

namespace App\Http\Controllers\Api\Platform\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Platform\StoreSupportNoteRequest;
use App\Http\Resources\SupportNoteResource;
use App\Models\Organization;
use App\Services\SupportNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PlatformSupportNoteController extends ApiController
{
    public function __construct(
        protected SupportNoteService $supportNoteService,
    ) {}

    public function index(int $organizationId): JsonResponse
    {
        $organization = Organization::query()->findOrFail($organizationId);
        $notes = $this->supportNoteService->paginateForOrganization($organization);

        return $this->success(
            SupportNoteResource::collection($notes->items()),
            [
                'pagination' => [
                    'current_page' => $notes->currentPage(),
                    'per_page' => $notes->perPage(),
                    'total' => $notes->total(),
                    'last_page' => $notes->lastPage(),
                ],
            ],
        );
    }

    public function store(StoreSupportNoteRequest $request, int $organizationId): JsonResponse
    {
        $organization = Organization::query()->findOrFail($organizationId);
        $note = $this->supportNoteService->create(
            $organization,
            $request->user('platform'),
            $request->validated('note'),
        );

        return $this->success(new SupportNoteResource($note), status: Response::HTTP_CREATED);
    }
}
