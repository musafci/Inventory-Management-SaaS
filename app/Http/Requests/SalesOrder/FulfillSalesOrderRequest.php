<?php

namespace App\Http\Requests\SalesOrder;

use Illuminate\Foundation\Http\FormRequest;

class FulfillSalesOrderRequest extends FormRequest
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
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_order_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
