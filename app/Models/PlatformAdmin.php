<?php

namespace App\Models;

use Database\Factories\PlatformAdminFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password'])]
class PlatformAdmin extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<PlatformAdminFactory> */
    use HasApiTokens, HasFactory;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
