<?php

namespace App\Http\Requests\Platform;

use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformOrganizationSubscriptionRequest extends FormRequest
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
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')],
            'status' => ['sometimes', 'string', Rule::enum(SubscriptionStatus::class)],
            'trial_ends_at' => ['sometimes', 'nullable', 'date'],
            'current_period_ends_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
