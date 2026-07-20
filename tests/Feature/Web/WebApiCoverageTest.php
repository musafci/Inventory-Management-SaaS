<?php

use App\Services\Web\ApiClient;

it('adds idempotency key for purchase and sales order create endpoints', function () {
    $client = new ApiClient;
    $reflection = new ReflectionClass(ApiClient::class);
    $methodRef = $reflection->getMethod('requiresIdempotencyKey');
    $methodRef->setAccessible(true);

    $requires = fn (string $method, string $endpoint): bool => $methodRef->invoke($client, $method, $endpoint);

    expect($requires('POST', '/v1/purchase-orders'))->toBeTrue();
    expect($requires('POST', '/v1/sales-orders'))->toBeTrue();
    expect($requires('POST', '/v1/purchase-orders/1/send'))->toBeFalse();
    expect($requires('POST', '/v1/products'))->toBeFalse();
});

it('stores refresh token metadata in web session service', function () {
    $service = app(\App\Services\Web\WebSessionService::class);

    $service->storeAuthSession(
        [
            'access_token' => 'access-test',
            'refresh_token' => 'refresh-test',
            'expires_in' => 3600,
        ],
        ['name' => 'Jane', 'email' => 'jane@test.com'],
        [['id' => 1, 'name' => 'Acme']],
    );

    expect(session('auth_token'))->toBe('access-test');
    expect(session('refresh_token'))->toBe('refresh-test');
    expect(session('token_expires_at'))->not->toBeNull();
    expect(session('user_name'))->toBe('Jane');
});
