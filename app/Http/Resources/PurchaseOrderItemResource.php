<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'quantity_ordered' => $this->quantity_ordered,
            'quantity_received' => $this->quantity_received,
            'quantity_remaining' => $this->quantityRemaining(),
            'unit_cost' => $this->unit_cost,
            'subtotal' => $this->subtotal,
        ];
    }
}
