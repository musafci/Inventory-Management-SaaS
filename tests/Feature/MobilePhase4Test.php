<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('notification preferences can be fetched and updated', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'notif-prefs@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->getJson('/api/v1/notifications/preferences', $headers)
        ->assertOk()
        ->assertJsonPath('data.preferences.low_stock', true)
        ->assertJsonPath('data.preferences.sales_order_status', true);

    $this->patchJson('/api/v1/notifications/preferences', [
        'preferences' => [
            'low_stock' => false,
            'sales_order_status' => true,
        ],
    ], $headers)
        ->assertOk()
        ->assertJsonPath('data.preferences.low_stock', false);
});

test('sales order print api returns html', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'print-api-so@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);

    $order = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => now()->toDateString(),
        'items' => [
            [
                'product_id' => $catalog['product_id'],
                'quantity' => 2,
                'unit_price' => '10.00',
            ],
        ],
    ], $headers)->assertCreated();

    $orderId = $order->json('data.id');

    $this->get("/api/v1/sales-orders/{$orderId}/print", $headers)
        ->assertOk()
        ->assertHeader('content-type', 'text/html; charset=UTF-8')
        ->assertSee('Sales Order', false);
});
