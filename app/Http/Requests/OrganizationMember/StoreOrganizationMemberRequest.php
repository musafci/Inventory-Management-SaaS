<?php

namespace App\Http\Requests\OrganizationMember;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationMemberRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'string', Rule::in(array_keys(RolesAndPermissionsSeeder::rolePermissionMap()))],
        ];
    }
}
