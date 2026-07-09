<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
