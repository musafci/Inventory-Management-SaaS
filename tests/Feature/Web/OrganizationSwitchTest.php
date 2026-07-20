<?php

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('authenticated user can switch active organization in web session', function () {
    $this->seed(DemoSeeder::class);

    $consultant = User::query()->where('email', 'consultant@demo.test')->firstOrFail();
    $organizations = $consultant->organizations()->orderBy('name')->get();

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'consultant@demo.test',
        'password' => 'password123',
    ])->assertOk();

    $this->withSession([
        'auth_token' => $login->json('data.token.access_token'),
        'refresh_token' => $login->json('data.token.refresh_token'),
        'token_expires_at' => now()->addHour()->toIso8601String(),
        'user_name' => $consultant->name,
        'user_email' => $consultant->email,
        'organizations' => $organizations->map(fn (Organization $org) => [
            'id' => $org->id,
            'name' => $org->name,
            'slug' => $org->slug,
            'role' => $org->pivot->role,
        ])->values()->all(),
        'organization_id' => $organizations->first()->id,
    ])->post('/organization/switch', [
        'organization_id' => $organizations->last()->id,
    ])->assertRedirect();

    expect(session('organization_id'))->toBe($organizations->last()->id);
});
