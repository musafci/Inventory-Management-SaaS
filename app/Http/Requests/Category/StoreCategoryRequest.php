<?php

namespace App\Http\Requests\Category;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
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
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('categories', 'slug')->where(
                    fn ($query) => $query->where('organization_id', app('currentOrganization')->id),
                ),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists(Category::class, 'id'),
            ],
        ];
    }
}
