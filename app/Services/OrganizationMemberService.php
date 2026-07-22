<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Services\RoleManagementService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OrganizationMemberService
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(): LengthAwarePaginator
    {
        $organization = app('currentOrganization');

        return User::query()
            ->whereHas('organizations', function ($query) use ($organization): void {
                $query->where('organizations.id', $organization->id);
            })
            ->with(['organizations' => function ($query) use ($organization): void {
                $query->where('organizations.id', $organization->id);
            }])
            ->orderBy('name')
            ->paginate(request()->integer('per_page', 15));
    }

    /**
     * @return array{user: User, created: bool}
     */
    public function store(array $data): array
    {
        $organization = app('currentOrganization');
        $role = $data['role'];

        $this->assertAssignableRole($role);

        return DB::transaction(function () use ($data, $organization, $role): array {
            $user = User::query()->where('email', $data['email'])->first();
            $created = false;

            if ($user === null) {
                $user = User::query()->create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'phone' => $data['phone'] ?? null,
                ]);
                $created = true;
            } elseif ($user->organizations()->where('organizations.id', $organization->id)->exists()) {
                throw ValidationException::withMessages([
                    'email' => ['This user is already a member of the organization.'],
                ]);
            }

            $user->organizations()->attach($organization->id, ['role' => $role]);

            setPermissionsTeamId($organization->id);
            $user->syncRoles([$role]);

            if ($user->default_organization_id === null) {
                $user->forceFill(['default_organization_id' => $organization->id])->save();
            }

            return [
                'user' => $user->fresh(['organizations']),
                'created' => $created,
            ];
        });
    }

    public function updateRole(User $member, string $role): User
    {
        $organization = app('currentOrganization');

        $this->assertAssignableRole($role);
        $this->assertMemberBelongsToOrganization($member, $organization);

        if ($this->isLastOwner($member, $organization) && $role !== 'Org Owner') {
            throw ValidationException::withMessages([
                'role' => ['Cannot change the role of the last organization owner.'],
            ]);
        }

        return DB::transaction(function () use ($member, $organization, $role): User {
            $member->organizations()->updateExistingPivot($organization->id, ['role' => $role]);

            setPermissionsTeamId($organization->id);
            $member->syncRoles([$role]);

            return $member->fresh(['organizations']);
        });
    }

    public function remove(User $member, User $actor): void
    {
        $organization = app('currentOrganization');

        $this->assertMemberBelongsToOrganization($member, $organization);

        if ($member->id === $actor->id) {
            throw ValidationException::withMessages([
                'user' => ['You cannot remove yourself from the organization.'],
            ]);
        }

        if ($this->isLastOwner($member, $organization)) {
            throw ValidationException::withMessages([
                'user' => ['Cannot remove the last organization owner.'],
            ]);
        }

        DB::transaction(function () use ($member, $organization): void {
            setPermissionsTeamId($organization->id);
            $member->syncRoles([]);
            $member->organizations()->detach($organization->id);

            if ((int) $member->default_organization_id === (int) $organization->id) {
                $nextOrgId = $member->organizations()->value('organizations.id');
                $member->forceFill(['default_organization_id' => $nextOrgId])->save();
            }
        });
    }

    /**
     * @return Collection<int, Organization>
     */
    public function organizationsForUser(User $user): Collection
    {
        return $user->organizations()->orderBy('name')->get();
    }

    protected function assertAssignableRole(string $role): void
    {
        app(RoleManagementService::class)->assertAssignableRole($role);
    }

    protected function assertMemberBelongsToOrganization(User $member, Organization $organization): void
    {
        if (! $member->organizations()->where('organizations.id', $organization->id)->exists()) {
            throw ValidationException::withMessages([
                'user' => ['User is not a member of this organization.'],
            ]);
        }
    }

    protected function isLastOwner(User $member, Organization $organization): bool
    {
        $pivotRole = $member->organizations()
            ->where('organizations.id', $organization->id)
            ->first()?->pivot?->role;

        if ($pivotRole !== 'Org Owner') {
            return false;
        }

        return $organization->users()
            ->wherePivot('role', 'Org Owner')
            ->count() <= 1;
    }
}
