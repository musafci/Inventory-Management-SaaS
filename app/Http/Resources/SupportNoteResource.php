<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportNoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'note' => $this->note,
            'created_at' => $this->created_at?->toISOString(),
            'platform_admin' => new PlatformAdminResource($this->whenLoaded('platformAdmin')),
        ];
    }
}
