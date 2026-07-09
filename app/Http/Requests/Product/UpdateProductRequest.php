<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        $productId = (int) $this->route('productId');

        return [
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'unit_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('units', 'id')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')
                    ->where(fn ($query) => $query->where('organization_id', $organizationId))
                    ->ignore($productId),
            ],
            'barcode' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')
                    ->where(fn ($query) => $query->where('organization_id', $organizationId))
                    ->ignore($productId),
            ],
            'cost_price' => ['sometimes', 'required', 'numeric', 'min:0', 'decimal:0,2'],
            'selling_price' => ['sometimes', 'required', 'numeric', 'min:0', 'decimal:0,2'],
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'reorder_point' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
