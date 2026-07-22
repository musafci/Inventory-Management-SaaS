<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'price_monthly' => $this->price_monthly,
            'price_annual' => $this->price_annual,
            'limits' => $this->limits,
            'is_custom' => $this->is_custom,
            'grace_buffer_percent' => $this->grace_buffer_percent,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
