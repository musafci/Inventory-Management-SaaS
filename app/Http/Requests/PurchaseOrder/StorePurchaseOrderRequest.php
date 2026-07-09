<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
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
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where('organization_id', $organizationId),
            ],
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('organization_id', $organizationId),
            ],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('organization_id', $organizationId),
            ],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
