<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesFulfillmentItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sales_fulfillment_id' => $this->sales_fulfillment_id,
            'sales_order_item_id' => $this->sales_order_item_id,
            'quantity_fulfilled' => $this->quantity_fulfilled,
            'sales_order_item' => new SalesOrderItemResource($this->whenLoaded('salesOrderItem')),
        ];
    }
}
