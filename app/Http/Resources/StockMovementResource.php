<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
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
            'type' => $this->type->value,
            'quantity' => $this->quantity,
            'note' => $this->note,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
