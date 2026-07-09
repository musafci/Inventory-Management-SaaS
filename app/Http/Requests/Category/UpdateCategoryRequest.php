<?php

namespace App\Http\Requests\Category;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
        $categoryId = (int) $this->route('categoryId');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('categories', 'slug')
                    ->where(fn ($query) => $query->where('organization_id', app('currentOrganization')->id))
                    ->ignore($categoryId),
            ],
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists(Category::class, 'id'),
                Rule::notIn([$categoryId]),
            ],
        ];
    }
}
