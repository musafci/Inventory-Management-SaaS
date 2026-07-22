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
            'deletion_requested_at' => $this->deletion_requested_at?->toISOString(),
            'deletion_scheduled_for' => $this->deletion_scheduled_for?->toISOString(),
            'users_count' => $this->when(isset($this->users_count), $this->users_count),
            'role' => $this->whenPivotLoaded('organization_user', fn () => $this->pivot->role),
            'subscription' => new OrganizationSubscriptionResource($this->whenLoaded('subscription')),
            'members' => UserResource::collection($this->whenLoaded('users')),
        ];
    }
}
