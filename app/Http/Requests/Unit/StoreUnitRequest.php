<?php

namespace App\Http\Requests\Unit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'symbol' => [
                'required',
                'string',
                'max:50',
                Rule::unique('units', 'symbol')->where(
                    fn ($query) => $query->where('organization_id', app('currentOrganization')->id),
                ),
            ],
        ];
    }
}
