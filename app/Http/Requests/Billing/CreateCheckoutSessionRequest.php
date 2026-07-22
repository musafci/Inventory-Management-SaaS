<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCheckoutSessionRequest extends FormRequest
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
            'plan_slug' => ['required', 'string', Rule::in(['starter', 'growth', 'business'])],
            'interval' => ['required', 'string', Rule::in(['monthly', 'yearly', 'month', 'year'])],
        ];
    }
}
