<?php

namespace App\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait LogsModelActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(\Spatie\Activitylog\Contracts\Activity $activity, string $eventName): void
    {
        $organizationId = $this->organization_id ?? null;

        if ($organizationId === null && app()->bound('currentOrganization')) {
            $organizationId = app('currentOrganization')->id;
        }

        if ($organizationId === null) {
            return;
        }

        $activity->organization_id = $organizationId;
        $activity->properties = $activity->properties->put('organization_id', $organizationId);
    }
}
