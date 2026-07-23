<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'supplier_id' => $this->supplier_id,
            'warehouse_id' => $this->warehouse_id,
            'po_number' => $this->po_number,
            'status' => $this->status->value,
            'order_date' => $this->order_date?->toDateString(),
            'expected_date' => $this->expected_date?->toDateString(),
            'total_amount' => $this->total_amount,
            'gross_subtotal' => $this->when(
                $this->relationLoaded('items'),
                fn (): string => $this->grossSubtotal(),
            ),
            'total_discount' => $this->when(
                $this->relationLoaded('items'),
                fn (): string => $this->totalDiscount(),
            ),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'amount_paid' => $this->netAmountPaid(),
            'amount_due' => $this->amountDue(),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
