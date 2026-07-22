<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;

class OrganizationDeletionService
{
    public function requestDeletion(Organization $organization): Organization
    {
        $graceDays = (int) config('subscription.deletion_grace_days', 30);

        $organization->forceFill([
            'deletion_requested_at' => now(),
            'deletion_scheduled_for' => now()->addDays($graceDays),
        ])->save();

        return $organization->fresh();
    }

    public function cancelDeletion(Organization $organization): Organization
    {
        $organization->forceFill([
            'deletion_requested_at' => null,
            'deletion_scheduled_for' => null,
        ])->save();

        return $organization->fresh();
    }

    public function processDueDeletions(): int
    {
        $deleted = 0;

        Organization::query()
            ->whereNotNull('deletion_scheduled_for')
            ->where('deletion_scheduled_for', '<=', now())
            ->each(function (Organization $organization) use (&$deleted): void {
                DB::transaction(function () use ($organization): void {
                    $organization->delete();
                });

                $deleted++;
            });

        return $deleted;
    }
}
