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
        if (isset($this->organization_id)) {
            $activity->properties = $activity->properties->put('organization_id', $this->organization_id);
        }
    }
}
