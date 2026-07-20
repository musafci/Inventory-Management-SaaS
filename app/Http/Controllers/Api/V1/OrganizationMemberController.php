<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\OrganizationMember\StoreOrganizationMemberRequest;
use App\Http\Requests\OrganizationMember\UpdateOrganizationMemberRequest;
use App\Http\Resources\OrganizationMemberResource;
use App\Models\User;
use App\Services\OrganizationMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class OrganizationMemberController extends ApiController
{
    public function __construct(
        protected OrganizationMemberService $memberService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $members = $this->memberService->paginate();

        return $this->success(
            OrganizationMemberResource::collection($members->items()),
            [
                'pagination' => [
                    'current_page' => $members->currentPage(),
                    'per_page' => $members->perPage(),
                    'total' => $members->total(),
                    'last_page' => $members->lastPage(),
                ],
            ],
        );
    }

    public function store(StoreOrganizationMemberRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $result = $this->memberService->store($request->validated());

        return $this->success(
            new OrganizationMemberResource($result['user']),
            status: 201,
        );
    }

    public function update(UpdateOrganizationMemberRequest $request, int $userId): JsonResponse
    {
        $member = $this->findMemberForCurrentOrganization($userId);

        $this->authorize('update', $member);

        $member = $this->memberService->updateRole($member, $request->validated('role'));

        return $this->success(new OrganizationMemberResource($member));
    }

    public function destroy(int $userId): Response
    {
        $member = $this->findMemberForCurrentOrganization($userId);

        $this->authorize('delete', $member);

        $this->memberService->remove($member, request()->user());

        return response()->noContent();
    }

    protected function findMemberForCurrentOrganization(int $userId): User
    {
        $organization = app('currentOrganization');

        return User::query()
            ->whereKey($userId)
            ->whereHas('organizations', function ($query) use ($organization): void {
                $query->where('organizations.id', $organization->id);
            })
            ->with(['organizations' => function ($query) use ($organization): void {
                $query->where('organizations.id', $organization->id);
            }])
            ->firstOrFail();
    }
}
