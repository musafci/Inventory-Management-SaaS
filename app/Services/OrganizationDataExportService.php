<?php

namespace App\Services;

use App\Jobs\ProcessOrganizationDataExportJob;
use App\Models\Organization;
use App\Models\OrganizationDataExport;
use App\Models\User;

class OrganizationDataExportService
{
    public function queueExport(Organization $organization, User $user): OrganizationDataExport
    {
        $export = OrganizationDataExport::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        ProcessOrganizationDataExportJob::dispatch($export->id);

        return $export;
    }
}
