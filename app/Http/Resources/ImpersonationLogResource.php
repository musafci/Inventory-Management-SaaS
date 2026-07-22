<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImpersonationLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform_admin_id' => $this->platform_admin_id,
            'organization_id' => $this->organization_id,
            'impersonated_user_id' => $this->impersonated_user_id,
            'reason' => $this->reason,
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'platform_admin' => new PlatformAdminResource($this->whenLoaded('platformAdmin')),
            'impersonated_user' => new UserResource($this->whenLoaded('impersonatedUser')),
        ];
    }
}
