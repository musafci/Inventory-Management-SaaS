<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsReceiptItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_order_item_id' => $this->purchase_order_item_id,
            'quantity_received' => $this->quantity_received,
            'purchase_order_item' => new PurchaseOrderItemResource($this->whenLoaded('purchaseOrderItem')),
        ];
    }
}
