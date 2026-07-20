<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $organization = app('currentOrganization');
        $membership = $this->organizations
            ->firstWhere('id', $organization->id);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'role' => $membership?->pivot?->role,
            'last_login_at' => $this->last_login_at?->toISOString(),
        ];
    }
}
