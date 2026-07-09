<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'payable_type' => $this->payable_type,
            'payable_id' => $this->payable_id,
            'amount' => $this->amount,
            'method' => $this->method->value,
            'status' => $this->status->value,
            'reference' => $this->reference,
            'note' => $this->note,
            'recorded_by' => $this->recorded_by,
            'paid_at' => $this->paid_at?->toISOString(),
            'payable' => $this->whenLoaded('payable', function () {
                return match ($this->payable_type) {
                    \App\Models\SalesOrder::class => new SalesOrderResource($this->payable),
                    \App\Models\PurchaseOrder::class => new PurchaseOrderResource($this->payable),
                    default => $this->payable,
                };
            }),
            'recorded_by_user' => new UserResource($this->whenLoaded('recordedBy')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
