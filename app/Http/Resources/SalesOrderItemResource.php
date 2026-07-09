<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderItemResource extends JsonResource
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
            'quantity' => $this->quantity,
            'quantity_fulfilled' => $this->quantity_fulfilled,
            'quantity_returned' => $this->quantity_returned,
            'unit_price' => $this->unit_price,
            'discount' => $this->discount,
            'subtotal' => $this->subtotal,
        ];
    }
}
