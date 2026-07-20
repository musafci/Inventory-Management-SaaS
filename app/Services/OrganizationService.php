<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Str;

class OrganizationService
{
    public function update(Organization $organization, array $data): Organization
    {
        if (array_key_exists('name', $data) && $data['name'] !== $organization->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $organization->id);
        }

        $organization->fill($data);
        $organization->save();

        return $organization->fresh();
    }

    protected function uniqueSlug(string $name, ?int $ignoreOrganizationId = null): string
    {
        $base = Str::slug($name) ?: 'organization';
        $slug = $base;
        $counter = 1;

        while (
            Organization::query()
                ->when($ignoreOrganizationId, fn ($query) => $query->whereKeyNot($ignoreOrganizationId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
