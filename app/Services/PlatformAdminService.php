<?php

namespace App\Services;

use App\Models\PlatformAdmin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PlatformAdminService
{
    /**
     * @return LengthAwarePaginator<int, PlatformAdmin>
     */
    public function paginate(): LengthAwarePaginator
    {
        return PlatformAdmin::query()
            ->orderBy('name')
            ->paginate(request()->integer('per_page', 15));
    }

    public function create(string $name, string $email, string $password): PlatformAdmin
    {
        if (PlatformAdmin::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['A platform admin with this email already exists.'],
            ]);
        }

        return PlatformAdmin::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
    }

    public function delete(PlatformAdmin $admin): void
    {
        if (PlatformAdmin::query()->count() <= 1) {
            throw ValidationException::withMessages([
                'admin' => ['Cannot delete the last platform admin.'],
            ]);
        }

        $admin->delete();
    }
}
