<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'warehouse_id' => $this->warehouse_id,
            'product_id' => $this->product_id,
            'quantity_on_hand' => $this->quantity_on_hand,
            'quantity_reserved' => $this->quantity_reserved,
            'quantity_available' => $this->quantity_available,
            'last_counted_at' => $this->last_counted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
