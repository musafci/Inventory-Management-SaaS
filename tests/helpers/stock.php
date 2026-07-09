<?php

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;

/**
 * @return array{
 *     organization: Organization,
 *     warehouse: Warehouse,
 *     product: Product,
 *     user: User,
 * }
 */
function bootstrapStockContext(): array
{
    $organization = Organization::factory()->create();
    app()->instance('currentOrganization', $organization);
    setPermissionsTeamId($organization->id);

    $user = User::factory()->create();
    $user->organizations()->attach($organization->id, ['role' => 'Org Owner']);

    $category = Category::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'name' => 'Stock Category',
        'slug' => 'stock-category',
    ]);

    $unit = Unit::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'name' => 'Piece',
        'symbol' => 'pcs',
    ]);

    $warehouse = Warehouse::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'name' => 'Main Warehouse',
        'is_default' => true,
    ]);

    $product = Product::withoutOrganizationScope()->create([
        'organization_id' => $organization->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'name' => 'Tracked Product',
        'sku' => 'TRACK-001',
        'cost_price' => 5,
        'selling_price' => 10,
        'tax_rate' => 0,
        'is_active' => true,
    ]);

    return compact('organization', 'warehouse', 'product', 'user');
}

/**
 * @param  array{
 *     organization: Organization,
 *     warehouse: Warehouse,
 *     product: Product,
 *     user: User,
 * }  $context
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function movementPayload(array $context, array $overrides = []): array
{
    return array_merge([
        'warehouse_id' => $context['warehouse']->id,
        'product_id' => $context['product']->id,
        'type' => StockMovementType::AdjustmentIn,
        'quantity' => 1,
        'created_by' => $context['user']->id,
    ], $overrides);
}

/**
 * @param  array{warehouse: Warehouse, product: Product}  $context
 */
function currentQuantityOnHand(array $context): int
{
    return (int) Stock::query()
        ->where('warehouse_id', $context['warehouse']->id)
        ->where('product_id', $context['product']->id)
        ->value('quantity_on_hand');
}

/**
 * @return array<string, string>
 */
function stockPostgresWorkerEnvironment(): array
{
    return [
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => 'pgsql',
        'DB_HOST' => getenv('STOCK_PG_HOST') ?: '127.0.0.1',
        'DB_PORT' => getenv('STOCK_PG_PORT') ?: '5433',
        'DB_DATABASE' => getenv('STOCK_PG_DATABASE') ?: 'inventory',
        'DB_USERNAME' => getenv('STOCK_PG_USERNAME') ?: 'inventory',
        'DB_PASSWORD' => getenv('STOCK_PG_PASSWORD') ?: 'secret',
    ];
}
