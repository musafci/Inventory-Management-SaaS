<?php

namespace App\Services;

use App\Models\FeatureFlag;
use App\Models\Organization;
use App\Models\OrganizationFeatureFlag;
use Illuminate\Support\Collection;

class FeatureFlagService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function flagsForOrganization(Organization $organization): Collection
    {
        $overrides = OrganizationFeatureFlag::query()
            ->where('organization_id', $organization->id)
            ->get()
            ->keyBy('feature_flag_id');

        return FeatureFlag::query()
            ->orderBy('key')
            ->get()
            ->map(function (FeatureFlag $flag) use ($overrides, $organization): array {
                $override = $overrides->get($flag->id);

                return [
                    'id' => $flag->id,
                    'key' => $flag->key,
                    'description' => $flag->description,
                    'default_enabled' => $flag->default_enabled,
                    'enabled' => $override?->enabled ?? $flag->default_enabled,
                    'has_override' => $override !== null,
                    'organization_id' => $organization->id,
                ];
            });
    }

    public function setOverride(Organization $organization, FeatureFlag $flag, bool $enabled): OrganizationFeatureFlag
    {
        return OrganizationFeatureFlag::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'feature_flag_id' => $flag->id,
            ],
            ['enabled' => $enabled],
        );
    }

    public function isEnabled(Organization $organization, string $key): bool
    {
        $flag = FeatureFlag::query()->where('key', $key)->first();

        if ($flag === null) {
            return false;
        }

        $override = OrganizationFeatureFlag::query()
            ->where('organization_id', $organization->id)
            ->where('feature_flag_id', $flag->id)
            ->first();

        return $override?->enabled ?? $flag->default_enabled;
    }
}
