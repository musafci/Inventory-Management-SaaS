<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesFulfillmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'sales_order_id' => $this->sales_order_id,
            'fulfilled_by' => $this->fulfilled_by,
            'note' => $this->note,
            'fulfilled_at' => $this->fulfilled_at?->toISOString(),
            'items' => SalesFulfillmentItemResource::collection($this->whenLoaded('items')),
            'sales_order' => new SalesOrderResource($this->whenLoaded('salesOrder')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
