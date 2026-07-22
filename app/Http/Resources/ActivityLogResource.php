<?php

namespace App\Http\Resources;

use App\Models\Activity;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Activity */
class ActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $service = app(ActivityLogService::class);

        return [
            'id' => $this->id,
            'event' => $this->event,
            'description' => $this->description,
            'log_name' => $this->log_name,
            'organization_id' => $this->organization_id,
            'organization' => $this->whenLoaded('organization', fn (): array => [
                'id' => $this->organization->id,
                'name' => $this->organization->name,
                'slug' => $this->organization->slug,
            ]),
            'subject' => [
                'type' => $this->subject_type ? class_basename($this->subject_type) : null,
                'type_class' => $this->subject_type,
                'id' => $this->subject_id,
                'label' => $service->resolveSubjectLabel($this->resource),
            ],
            'causer' => $this->when($this->causer !== null, fn (): array => [
                'id' => $this->causer?->id,
                'name' => $this->causer?->name,
                'email' => $this->causer?->email,
            ]),
            'changes' => [
                'attributes' => data_get($this->properties, 'attributes'),
                'old' => data_get($this->properties, 'old'),
            ],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
