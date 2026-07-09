<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordRefundRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'paid_at' => ['nullable', 'date'],
            'return_items' => ['sometimes', 'array'],
            'return_items.*.sales_order_item_id' => ['required', 'integer'],
            'return_items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
