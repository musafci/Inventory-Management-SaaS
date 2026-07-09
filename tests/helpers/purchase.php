<?php

/**
 * @return array{
 *     supplier_id: int,
 *     warehouse_id: int,
 *     product_id: int,
 * }
 */
function createPurchasingCatalog(object $test, array $headers): array
{
    $category = $test->postJson('/api/v1/categories', ['name' => 'PO Cat'], $headers)->assertCreated();
    $unit = $test->postJson('/api/v1/units', ['name' => 'Piece', 'symbol' => 'pcs'], $headers)->assertCreated();
    $product = $test->postJson('/api/v1/products', [
        'category_id' => $category->json('data.id'),
        'unit_id' => $unit->json('data.id'),
        'name' => 'Purchased Item',
        'sku' => 'PO-'.fake()->unique()->numerify('####'),
        'cost_price' => 5,
        'selling_price' => 10,
    ], $headers)->assertCreated();

    $test->postJson('/api/v1/warehouses', ['name' => 'Receiving Warehouse'], $headers)->assertCreated();
    $warehouseId = $test->getJson('/api/v1/warehouses', $headers)->json('data.0.id');

    $supplier = $test->postJson('/api/v1/suppliers', [
        'name' => 'Acme Supplies',
        'contact_person' => 'Sam Supplier',
        'email' => 'sam@acme.test',
    ], $headers)->assertCreated();

    return [
        'supplier_id' => $supplier->json('data.id'),
        'warehouse_id' => $warehouseId,
        'product_id' => $product->json('data.id'),
    ];
}

/**
 * @param  array{supplier_id: int, warehouse_id: int, product_id: int}  $catalog
 * @return \Illuminate\Testing\TestResponse
 */
function createDraftPurchaseOrder(object $test, array $headers, array $catalog, array $overrides = [])
{
    return $test->postJson('/api/v1/purchase-orders', array_merge([
        'supplier_id' => $catalog['supplier_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'expected_date' => '2026-07-16',
        'items' => [
            [
                'product_id' => $catalog['product_id'],
                'quantity_ordered' => 20,
                'unit_cost' => 5,
            ],
        ],
    ], $overrides), withIdempotencyKey($headers));
}

/**
 * @return array{
 *     organization: \App\Models\Organization,
 *     warehouse: \App\Models\Warehouse,
 *     products: array{low: \App\Models\Product, high: \App\Models\Product},
 *     user: \App\Models\User,
 *     supplier: \App\Models\Supplier,
 * }
 */
function bootstrapOverlappingReceiptContext(): array
{
    $organization = \App\Models\Organization::factory()->create();
    app()->instance('currentOrganization', $organization);
    setPermissionsTeamId($organization->id);

    $user = \App\Models\User::factory()->create();
    $user->organizations()->attach($organization->id, ['role' => 'Org Owner']);

    $category = \App\Models\Category::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'name' => 'Receipt Category',
        'slug' => 'receipt-category',
    ]);

    $unit = \App\Models\Unit::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'name' => 'Piece',
        'symbol' => 'pcs',
    ]);

    $warehouse = \App\Models\Warehouse::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'name' => 'Receiving Warehouse',
        'is_default' => true,
    ]);

    $lowProduct = \App\Models\Product::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'name' => 'Alpha Product',
        'sku' => 'ALPHA-001',
        'cost_price' => 5,
        'selling_price' => 10,
        'tax_rate' => 0,
        'is_active' => true,
    ]);

    $highProduct = \App\Models\Product::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'name' => 'Zulu Product',
        'sku' => 'ZULU-001',
        'cost_price' => 7,
        'selling_price' => 14,
        'tax_rate' => 0,
        'is_active' => true,
    ]);

    if ($lowProduct->id > $highProduct->id) {
        [$lowProduct, $highProduct] = [$highProduct, $lowProduct];
    }

    $supplier = \App\Models\Supplier::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'name' => 'Overlap Supplier',
    ]);

    return [
        'organization' => $organization,
        'warehouse' => $warehouse,
        'products' => [
            'low' => $lowProduct,
            'high' => $highProduct,
        ],
        'user' => $user,
        'supplier' => $supplier,
    ];
}

/**
 * @param  array{
 *     organization: \App\Models\Organization,
 *     warehouse: \App\Models\Warehouse,
 *     products: array{low: \App\Models\Product, high: \App\Models\Product},
 *     user: \App\Models\User,
 *     supplier: \App\Models\Supplier,
 * }  $context
 */
function createSentPurchaseOrderWithProducts(
    array $context,
    int $quantityPerProduct = 20,
): \App\Models\PurchaseOrder {
    $purchaseOrder = app(\App\Services\PurchaseOrderService::class)->create([
        'supplier_id' => $context['supplier']->id,
        'warehouse_id' => $context['warehouse']->id,
        'order_date' => '2026-07-09',
        'items' => [
            [
                'product_id' => $context['products']['low']->id,
                'quantity_ordered' => $quantityPerProduct,
                'unit_cost' => 5,
            ],
            [
                'product_id' => $context['products']['high']->id,
                'quantity_ordered' => $quantityPerProduct,
                'unit_cost' => 7,
            ],
        ],
    ]);

    return app(\App\Services\PurchaseOrderService::class)->send($purchaseOrder);
}

/**
 * @param  array{warehouse: \App\Models\Warehouse, products: array{low: \App\Models\Product, high: \App\Models\Product}}  $context
 */
function stockQuantityForProduct(array $context, int $productId): int
{
    return (int) \App\Models\Stock::query()
        ->where('warehouse_id', $context['warehouse']->id)
        ->where('product_id', $productId)
        ->value('quantity_on_hand');
}
