<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'phone' => $this->phone,
            'plan' => $this->plan,
            'status' => $this->status,
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'users_count' => $this->when(isset($this->users_count), $this->users_count),
            'role' => $this->whenPivotLoaded('organization_user', fn () => $this->pivot->role),
        ];
    }
}
