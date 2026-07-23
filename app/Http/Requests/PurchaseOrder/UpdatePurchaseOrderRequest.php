<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends FormRequest
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
            'supplier_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where('organization_id', $organizationId),
            ],
            'warehouse_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('organization_id', $organizationId),
            ],
            'order_date' => ['sometimes', 'required', 'date'],
            'expected_date' => ['sometimes', 'nullable', 'date'],
            'items' => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required_with:items',
                'integer',
                Rule::exists('products', 'id')->where('organization_id', $organizationId),
            ],
            'items.*.quantity_ordered' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.discount' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
