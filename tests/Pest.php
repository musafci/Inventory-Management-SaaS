<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

require_once __DIR__.'/helpers/stock.php';
require_once __DIR__.'/helpers/purchase.php';
require_once __DIR__.'/helpers/sales.php';

/**
 * @return array<string, string>
 */
function withIdempotencyKey(array $headers, ?string $key = null): array
{
    return array_merge($headers, [
        'Idempotency-Key' => $key ?? (string) \Illuminate\Support\Str::uuid(),
    ]);
}

function validRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'organization_name' => 'Acme Inventory',
        'name' => 'Jane Owner',
        'email' => 'jane@acme.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '+15551234567',
    ], $overrides);
}
