<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SalesOrderStatus;
use App\Enums\StockMovementType;
use App\Models\Payment;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->setUpPassport();
});

test('sales order creation is written to the activity log with actor and changed attributes', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'audit-so@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);

    $response = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 2, 'unit_price' => 10],
        ],
    ], withIdempotencyKey($headers))->assertCreated();

    $salesOrderId = $response->json('data.id');

    $activity = Activity::query()
        ->where('subject_type', SalesOrder::class)
        ->where('subject_id', $salesOrderId)
        ->where('event', 'created')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($org['response']->json('data.user.id'))
        ->and(data_get($activity->properties, 'attributes.status'))->toBe(SalesOrderStatus::Draft->value)
        ->and(data_get($activity->properties, 'attributes.order_number'))->toBe('SO-000001')
        ->and($activity->properties->get('organization_id'))->toBe($org['organization_id']);
});

test('stock movement and payment mutations are audited with dirty attribute tracking', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'audit-pay@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    $order = createShippedSalesOrderForPayment($this, $headers, $catalog);

    $this->postJson('/api/v1/sales-orders/'.$order['sales_order_id'].'/pay', [
        'amount' => 75,
        'method' => PaymentMethod::Cash->value,
    ], $headers)->assertCreated();

    $payment = Payment::query()->firstOrFail();

    $paymentActivity = Activity::query()
        ->where('subject_type', Payment::class)
        ->where('subject_id', $payment->id)
        ->where('event', 'created')
        ->first();

    expect($paymentActivity)->not->toBeNull()
        ->and(data_get($paymentActivity->properties, 'attributes.amount'))->toBe('75.00')
        ->and(data_get($paymentActivity->properties, 'attributes.status'))->toBe(PaymentStatus::Completed->value);

    $movement = StockMovement::query()->where('type', StockMovementType::SaleOut)->firstOrFail();

    $movementActivity = Activity::query()
        ->where('subject_type', StockMovement::class)
        ->where('subject_id', $movement->id)
        ->where('event', 'created')
        ->first();

    expect($movementActivity)->not->toBeNull()
        ->and(data_get($movementActivity->properties, 'attributes.quantity'))->toBe($movement->quantity);
});
