<?php

namespace App\Http\Requests\SalesOrder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesOrderRequest extends FormRequest
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
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('organization_id', $organizationId),
            ],
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('organization_id', $organizationId),
            ],
            'order_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('organization_id', $organizationId),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
