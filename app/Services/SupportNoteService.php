<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\PlatformAdmin;
use App\Models\SupportNote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupportNoteService
{
    /**
     * @return LengthAwarePaginator<int, SupportNote>
     */
    public function paginateForOrganization(Organization $organization): LengthAwarePaginator
    {
        return SupportNote::query()
            ->with('platformAdmin')
            ->where('organization_id', $organization->id)
            ->orderByDesc('created_at')
            ->paginate(request()->integer('per_page', 20));
    }

    public function create(Organization $organization, PlatformAdmin $admin, string $note): SupportNote
    {
        return SupportNote::query()->create([
            'organization_id' => $organization->id,
            'platform_admin_id' => $admin->id,
            'note' => $note,
        ])->load('platformAdmin');
    }
}
