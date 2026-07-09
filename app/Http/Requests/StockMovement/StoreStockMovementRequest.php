<?php

namespace App\Http\Requests\StockMovement;

use App\Enums\StockMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = app('currentOrganization')->id;

        return [
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'type' => [
                'required',
                new Enum(StockMovementType::class),
                Rule::in(array_map(
                    fn (StockMovementType $type) => $type->value,
                    StockMovementType::manualAdjustmentTypes(),
                )),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
