<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'cost_price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'selling_price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'reorder_point' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
