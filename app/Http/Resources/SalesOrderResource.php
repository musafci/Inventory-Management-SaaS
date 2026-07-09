<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'customer_id' => $this->customer_id,
            'warehouse_id' => $this->warehouse_id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'order_date' => $this->order_date?->toDateString(),
            'total_amount' => $this->total_amount,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'items' => SalesOrderItemResource::collection($this->whenLoaded('items')),
            'amount_paid' => $this->netAmountPaid(),
            'amount_due' => $this->amountDue(),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
