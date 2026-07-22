<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeatureFlagResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'] ?? null,
            'key' => $this->resource['key'] ?? null,
            'description' => $this->resource['description'] ?? null,
            'default_enabled' => $this->resource['default_enabled'] ?? false,
            'enabled' => $this->resource['enabled'] ?? false,
            'has_override' => $this->resource['has_override'] ?? false,
            'organization_id' => $this->resource['organization_id'] ?? null,
        ];
    }
}
