<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;

class MigrateOrganizationPermissions extends Command
{
    protected $signature = 'rbac:migrate-organizations';

    protected $description = 'Reseed granular permissions and default roles for all organizations';

    public function handle(RolesAndPermissionsSeeder $seeder): int
    {
        $this->info('Migrating organization roles and permissions...');

        Organization::query()->orderBy('id')->each(function (Organization $organization) use ($seeder): void {
            app()->instance('currentOrganization', $organization);
            setPermissionsTeamId($organization->id);
            $seeder->seedRolesForOrganization($organization);
            $this->line("Updated organization #{$organization->id} ({$organization->name})");
        });

        $this->info('Done.');

        return self::SUCCESS;
    }
}
