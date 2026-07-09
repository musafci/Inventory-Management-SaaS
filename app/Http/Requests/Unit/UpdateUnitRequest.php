<?php

namespace App\Http\Requests\Unit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
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
        $unitId = (int) $this->route('unitId');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'symbol' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('units', 'symbol')
                    ->where(fn ($query) => $query->where('organization_id', app('currentOrganization')->id))
                    ->ignore($unitId),
            ],
        ];
    }
}
