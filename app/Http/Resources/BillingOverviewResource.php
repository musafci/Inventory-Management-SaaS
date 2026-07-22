<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingOverviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'subscription' => $this->resource['subscription'] !== null
                ? new OrganizationSubscriptionResource($this->resource['subscription'])
                : null,
            'available_plans' => PlanResource::collection($this->resource['available_plans'] ?? []),
            'stripe_configured' => $this->resource['stripe_configured'],
        ];
    }
}
