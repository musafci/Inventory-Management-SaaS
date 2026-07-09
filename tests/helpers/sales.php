<?php

use App\Enums\StockMovementType;

/**
 * @return array{
 *     customer_id: int,
 *     warehouse_id: int,
 *     product_id: int,
 * }
 */
function createSalesCatalog(object $test, array $headers): array
{
    $category = $test->postJson('/api/v1/categories', ['name' => 'SO Cat'], $headers)->assertCreated();
    $unit = $test->postJson('/api/v1/units', ['name' => 'Piece', 'symbol' => 'pcs'], $headers)->assertCreated();
    $product = $test->postJson('/api/v1/products', [
        'category_id' => $category->json('data.id'),
        'unit_id' => $unit->json('data.id'),
        'name' => 'Sold Item',
        'sku' => 'SO-'.fake()->unique()->numerify('####'),
        'cost_price' => 5,
        'selling_price' => 15,
    ], $headers)->assertCreated();

    $test->postJson('/api/v1/warehouses', ['name' => 'Fulfillment Warehouse'], $headers)->assertCreated();
    $warehouseId = $test->getJson('/api/v1/warehouses', $headers)->json('data.0.id');

    $customer = $test->postJson('/api/v1/customers', [
        'name' => 'Walk-in Customer',
        'email' => 'walkin@customer.test',
    ], $headers)->assertCreated();

    return [
        'customer_id' => $customer->json('data.id'),
        'warehouse_id' => $warehouseId,
        'product_id' => $product->json('data.id'),
    ];
}

/**
 * @param  array{warehouse_id: int, product_id: int}  $catalog
 */
function seedStockForSales(object $test, array $headers, array $catalog, int $quantity): void
{
    $test->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $catalog['warehouse_id'],
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => $quantity,
    ], $headers)->assertCreated();
}

/**
 * @return array{sales_order_id: int, line_item_id: int, total_amount: string}
 */
function createShippedSalesOrderForPayment(object $test, array $headers, array $catalog, int $quantity = 5, float $unitPrice = 15): array
{
    seedStockForSales($test, $headers, $catalog, 50);

    $salesOrder = $test->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => $quantity, 'unit_price' => $unitPrice],
        ],
    ], withIdempotencyKey($headers))->assertCreated();

    $salesOrderId = $salesOrder->json('data.id');
    $lineItemId = $salesOrder->json('data.items.0.id');
    $totalAmount = $salesOrder->json('data.total_amount');

    $test->postJson("/api/v1/sales-orders/{$salesOrderId}/confirm", [], $headers)->assertOk();
    $test->postJson("/api/v1/sales-orders/{$salesOrderId}/fulfill", [
        'items' => [['sales_order_item_id' => $lineItemId, 'quantity' => $quantity]],
    ], $headers)->assertCreated();

    return [
        'sales_order_id' => $salesOrderId,
        'line_item_id' => $lineItemId,
        'total_amount' => $totalAmount,
    ];
}

/**
 * @return array{
 *     organization: \App\Models\Organization,
 *     warehouse: \App\Models\Warehouse,
 *     product: \App\Models\Product,
 *     customer: \App\Models\Customer,
 *     user: \App\Models\User,
 * }
 */
function bootstrapSalesConfirmContext(): array
{
    $organization = \App\Models\Organization::factory()->create();
    app()->instance('currentOrganization', $organization);
    setPermissionsTeamId($organization->id);

    $user = \App\Models\User::factory()->create();
    $user->organizations()->attach($organization->id, ['role' => 'Org Owner']);

    $category = \App\Models\Category::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'name' => 'Sales Category',
        'slug' => 'sales-category',
    ]);

    $unit = \App\Models\Unit::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'name' => 'Piece',
        'symbol' => 'pcs',
    ]);

    $warehouse = \App\Models\Warehouse::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'name' => 'Sales Warehouse',
        'is_default' => true,
    ]);

    $product = \App\Models\Product::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'name' => 'Confirm Product',
        'sku' => 'CONFIRM-001',
        'cost_price' => 5,
        'selling_price' => 15,
        'tax_rate' => 0,
        'is_active' => true,
    ]);

    $customer = \App\Models\Customer::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'name' => 'Concurrency Customer',
        'email' => 'concurrency@customer.test',
    ]);

    return compact('organization', 'warehouse', 'product', 'customer', 'user');
}

/**
 * @param  array{
 *     warehouse: \App\Models\Warehouse,
 *     product: \App\Models\Product,
 *     customer: \App\Models\Customer,
 *     user: \App\Models\User,
 * }  $context
 */
function seedSalesConfirmStock(array $context, int $quantityOnHand): void
{
    app(\App\Services\StockService::class)->recordMovement([
        'warehouse_id' => $context['warehouse']->id,
        'product_id' => $context['product']->id,
        'type' => StockMovementType::AdjustmentIn,
        'quantity' => $quantityOnHand,
        'created_by' => $context['user']->id,
    ]);
}

/**
 * @param  array{
 *     customer: \App\Models\Customer,
 *     warehouse: \App\Models\Warehouse,
 *     product: \App\Models\Product,
 * }  $context
 */
function createDraftSalesOrderForConfirm(array $context, int $quantity): \App\Models\SalesOrder
{
    return app(\App\Services\SalesOrderService::class)->create([
        'customer_id' => $context['customer']->id,
        'warehouse_id' => $context['warehouse']->id,
        'order_date' => '2026-07-09',
        'items' => [
            [
                'product_id' => $context['product']->id,
                'quantity' => $quantity,
                'unit_price' => 15,
                'discount' => 0,
            ],
        ],
    ]);
}

/**
 * @param  array{warehouse: \App\Models\Warehouse, product: \App\Models\Product}  $context
 */
function reservedQuantityForProduct(array $context): int
{
    return (int) \App\Models\Stock::query()
        ->where('warehouse_id', $context['warehouse']->id)
        ->where('product_id', $context['product']->id)
        ->value('quantity_reserved');
}

/**
 * @param  array{warehouse: \App\Models\Warehouse, product: \App\Models\Product}  $context
 */
function onHandQuantityForProduct(array $context): int
{
    return (int) \App\Models\Stock::query()
        ->where('warehouse_id', $context['warehouse']->id)
        ->where('product_id', $context['product']->id)
        ->value('quantity_on_hand');
}

/**
 * @return array{
 *     organization: \App\Models\Organization,
 *     warehouse: \App\Models\Warehouse,
 *     products: array{low: \App\Models\Product, high: \App\Models\Product},
 *     customer: \App\Models\Customer,
 *     user: \App\Models\User,
 * }
 */
function bootstrapOverlappingSalesConfirmContext(): array
{
    $organization = \App\Models\Organization::factory()->create();
    app()->instance('currentOrganization', $organization);
    setPermissionsTeamId($organization->id);

    $user = \App\Models\User::factory()->create();
    $user->organizations()->attach($organization->id, ['role' => 'Org Owner']);

    $category = \App\Models\Category::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'name' => 'Overlap Sales Category',
        'slug' => 'overlap-sales-category',
    ]);

    $unit = \App\Models\Unit::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'name' => 'Piece',
        'symbol' => 'pcs',
    ]);

    $warehouse = \App\Models\Warehouse::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'name' => 'Overlap Sales Warehouse',
        'is_default' => true,
    ]);

    $lowProduct = \App\Models\Product::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'name' => 'Alpha Product',
        'sku' => 'SALES-ALPHA-001',
        'cost_price' => 5,
        'selling_price' => 15,
        'tax_rate' => 0,
        'is_active' => true,
    ]);

    $highProduct = \App\Models\Product::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'name' => 'Zulu Product',
        'sku' => 'SALES-ZULU-001',
        'cost_price' => 7,
        'selling_price' => 20,
        'tax_rate' => 0,
        'is_active' => true,
    ]);

    if ($lowProduct->id > $highProduct->id) {
        [$lowProduct, $highProduct] = [$highProduct, $lowProduct];
    }

    $customer = \App\Models\Customer::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'name' => 'Overlap Sales Customer',
        'email' => 'overlap-sales@customer.test',
    ]);

    return [
        'organization' => $organization,
        'warehouse' => $warehouse,
        'products' => [
            'low' => $lowProduct,
            'high' => $highProduct,
        ],
        'customer' => $customer,
        'user' => $user,
    ];
}

/**
 * @param  array{
 *     customer: \App\Models\Customer,
 *     warehouse: \App\Models\Warehouse,
 * }  $context
 * @param  list<int>  $productIdsInLineOrder
 */
function createDraftSalesOrderWithProductLineOrder(
    array $context,
    array $productIdsInLineOrder,
    int $quantityPerLine,
): \App\Models\SalesOrder {
    $items = array_map(
        fn (int $productId): array => [
            'product_id' => $productId,
            'quantity' => $quantityPerLine,
            'unit_price' => 15,
            'discount' => 0,
        ],
        $productIdsInLineOrder,
    );

    return app(\App\Services\SalesOrderService::class)->create([
        'customer_id' => $context['customer']->id,
        'warehouse_id' => $context['warehouse']->id,
        'order_date' => '2026-07-09',
        'items' => $items,
    ]);
}

/**
 * @param  array{
 *     warehouse: \App\Models\Warehouse,
 *     products: array{low: \App\Models\Product, high: \App\Models\Product},
 *     user: \App\Models\User,
 * }  $context
 */
function seedOverlappingSalesConfirmStock(array $context, int $quantityPerProduct): void
{
    foreach ($context['products'] as $product) {
        app(\App\Services\StockService::class)->recordMovement([
            'warehouse_id' => $context['warehouse']->id,
            'product_id' => $product->id,
            'type' => StockMovementType::AdjustmentIn,
            'quantity' => $quantityPerProduct,
            'created_by' => $context['user']->id,
        ]);
    }
}

/**
 * @param  array{warehouse: \App\Models\Warehouse}  $context
 */
function reservedQuantityForProductId(array $context, int $productId): int
{
    return (int) \App\Models\Stock::query()
        ->where('warehouse_id', $context['warehouse']->id)
        ->where('product_id', $productId)
        ->value('quantity_reserved');
}

/**
 * @param  array{
 *     customer: \App\Models\Customer,
 *     warehouse: \App\Models\Warehouse,
 *     product: \App\Models\Product,
 * }  $context
 */
function createConfirmedSalesOrderForFulfill(array $context, int $quantity): \App\Models\SalesOrder
{
    $salesOrder = createDraftSalesOrderForConfirm($context, $quantity);

    return app(\App\Services\SalesOrderService::class)->confirm($salesOrder);
}

/**
 * @param  array{
 *     customer: \App\Models\Customer,
 *     warehouse: \App\Models\Warehouse,
 * }  $context
 * @param  list<int>  $productIdsInLineOrder
 */
function createConfirmedSalesOrderWithProductLineOrder(
    array $context,
    array $productIdsInLineOrder,
    int $quantityPerLine,
): \App\Models\SalesOrder {
    $salesOrder = createDraftSalesOrderWithProductLineOrder($context, $productIdsInLineOrder, $quantityPerLine);

    return app(\App\Services\SalesOrderService::class)->confirm($salesOrder);
}

/**
 * @param  array{warehouse: \App\Models\Warehouse, product: \App\Models\Product}  $context
 */
function saleOutQuantityForProduct(array $context): int
{
    return (int) \App\Models\StockMovement::query()
        ->where('warehouse_id', $context['warehouse']->id)
        ->where('product_id', $context['product']->id)
        ->where('type', StockMovementType::SaleOut)
        ->sum('quantity');
}

/**
 * @param  array{warehouse: \App\Models\Warehouse, product: \App\Models\Product}  $context
 */
function returnInQuantityForProduct(array $context): int
{
    return (int) \App\Models\StockMovement::query()
        ->where('warehouse_id', $context['warehouse']->id)
        ->where('product_id', $context['product']->id)
        ->where('type', StockMovementType::ReturnIn)
        ->sum('quantity');
}

/**
 * @param  array{
 *     customer: \App\Models\Customer,
 *     warehouse: \App\Models\Warehouse,
 *     product: \App\Models\Product,
 *     user: \App\Models\User,
 * }  $context
 * @return array{
 *     sales_order: \App\Models\SalesOrder,
 *     line_item_id: int,
 *     refund_amount: string,
 *     return_quantity: int,
 * }
 */
function createShippedAndPaidSalesOrderForRefund(array $context, int $quantity): array
{
    $salesOrder = createConfirmedSalesOrderForFulfill($context, $quantity);
    $lineItem = $salesOrder->items->firstOrFail();

    app(\App\Services\SalesOrderFulfillmentService::class)->fulfill(
        $salesOrder,
        ['items' => [['sales_order_item_id' => $lineItem->id, 'quantity' => $quantity]]],
        $context['user']->id,
    );

    $salesOrder->refresh();

    app(\App\Services\PaymentService::class)->recordSalesPayment(
        $salesOrder,
        [
            'amount' => (float) $salesOrder->total_amount,
            'method' => \App\Enums\PaymentMethod::Cash,
        ],
        $context['user']->id,
    );

    $salesOrder->refresh();

    return [
        'sales_order' => $salesOrder,
        'line_item_id' => $lineItem->id,
        'refund_amount' => (string) $salesOrder->total_amount,
        'return_quantity' => $quantity,
    ];
}
