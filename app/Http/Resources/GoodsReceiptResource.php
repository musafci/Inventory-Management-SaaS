<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsReceiptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'purchase_order_id' => $this->purchase_order_id,
            'received_by' => $this->received_by,
            'note' => $this->note,
            'received_at' => $this->received_at?->toISOString(),
            'items' => GoodsReceiptItemResource::collection($this->whenLoaded('items')),
            'purchase_order' => new PurchaseOrderResource($this->whenLoaded('purchaseOrder')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
