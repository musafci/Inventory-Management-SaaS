<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Support\ListSearch;
use App\Support\UniqueConstraintViolation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class StockService
{
    /**
     * Append a stock movement and let StockMovementObserver apply the quantity delta.
     *
     * All stock quantity changes MUST go through this method — never write to
     * stocks.quantity_on_hand directly.
     *
     * @param  array{
     *     warehouse_id: int,
     *     product_id: int,
     *     type: StockMovementType|string,
     *     quantity: int,
     *     note?: string|null,
     *     reference_type?: string|null,
     *     reference_id?: int|null,
     *     created_by: int,
     * }  $data
     */
    public function recordMovement(array $data): StockMovement
    {
        return DB::transaction(function () use ($data): StockMovement {
            $type = $data['type'] instanceof StockMovementType
                ? $data['type']
                : StockMovementType::from($data['type']);

            $quantity = (int) $data['quantity'];
            $warehouseId = (int) $data['warehouse_id'];
            $productId = (int) $data['product_id'];

            $this->validateMovementQuantity($quantity);
            $this->assertWarehouseAndProductBelongToCurrentOrganization($warehouseId, $productId);

            // Serialize concurrent writers on the same stock row before inserting the ledger entry.
            $this->lockStockRow($warehouseId, $productId);

            return StockMovement::query()->create([
                'organization_id' => $this->currentOrganizationId(),
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'type' => $type,
                'quantity' => $quantity,
                'note' => $data['note'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'created_by' => (int) $data['created_by'],
            ]);
        });
    }

    /**
     * Record a warehouse transfer as paired ledger entries (out from source, in to destination).
     *
     * @return array{out: StockMovement, in: StockMovement}
     */
    public function recordTransfer(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $quantity = (int) $data['quantity'];
            $fromWarehouseId = (int) $data['from_warehouse_id'];
            $toWarehouseId = (int) $data['to_warehouse_id'];
            $productId = (int) $data['product_id'];

            if ($fromWarehouseId === $toWarehouseId) {
                throw ValidationException::withMessages([
                    'to_warehouse_id' => ['Transfer destination must differ from the source warehouse.'],
                ]);
            }

            $this->validateMovementQuantity($quantity);
            $this->assertWarehouseAndProductBelongToCurrentOrganization($fromWarehouseId, $productId);
            $this->assertWarehouseAndProductBelongToCurrentOrganization($toWarehouseId, $productId);

            $shared = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'note' => $data['note'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'created_by' => (int) $data['created_by'],
            ];

            $transferOut = $this->recordMovement([
                ...$shared,
                'warehouse_id' => $fromWarehouseId,
                'type' => StockMovementType::TransferOut,
            ]);

            $transferIn = $this->recordMovement([
                ...$shared,
                'warehouse_id' => $toWarehouseId,
                'type' => StockMovementType::TransferIn,
            ]);

            return [
                'out' => $transferOut,
                'in' => $transferIn,
            ];
        });
    }

    /**
     * @return LengthAwarePaginator<int, Stock>
     */
    public function paginateStocks(): LengthAwarePaginator
    {
        $query = Stock::query()->with(['warehouse', 'product']);
        ListSearch::apply($query, [
            ['relation' => 'product', 'columns' => ['name', 'sku']],
            ['relation' => 'warehouse', 'columns' => ['name']],
        ]);

        return QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::exact('warehouse_id'),
                AllowedFilter::exact('product_id'),
                AllowedFilter::callback('low_stock', function (Builder $query, mixed $value): void {
                    if (! filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                        return;
                    }

                    $query->whereHas('product', function (Builder $productQuery): void {
                        $productQuery
                            ->whereNotNull('reorder_point')
                            ->whereColumn(
                                'products.reorder_point',
                                '>=',
                                DB::raw('(stocks.quantity_on_hand - stocks.quantity_reserved)'),
                            );
                    });
                }),
            )
            ->allowedSorts('quantity_on_hand')
            ->defaultSort('-quantity_on_hand')
            ->paginate(request()->integer('per_page', 15));
    }

    /**
     * @return LengthAwarePaginator<int, StockMovement>
     */
    public function paginateMovements(): LengthAwarePaginator
    {
        $query = StockMovement::query()->with(['warehouse', 'product', 'createdBy']);
        ListSearch::apply($query, [
            ['relation' => 'product', 'columns' => ['name', 'sku']],
            ['relation' => 'warehouse', 'columns' => ['name']],
            ['columns' => ['note']],
        ]);

        return QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::exact('warehouse_id'),
                AllowedFilter::exact('product_id'),
                AllowedFilter::exact('type'),
                AllowedFilter::callback('created_from', function (Builder $query, mixed $value): void {
                    if (is_string($value) && $value !== '') {
                        $query->whereDate('created_at', '>=', $value);
                    }
                }),
                AllowedFilter::callback('created_to', function (Builder $query, mixed $value): void {
                    if (is_string($value) && $value !== '') {
                        $query->whereDate('created_at', '<=', $value);
                    }
                }),
            )
            ->allowedSorts('created_at')
            ->defaultSort('-created_at')
            ->paginate(request()->integer('per_page', 15));
    }

    protected function validateMovementQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => ['Quantity must be greater than zero.'],
            ]);
        }
    }

    protected function assertWarehouseAndProductBelongToCurrentOrganization(int $warehouseId, int $productId): void
    {
        Warehouse::query()->whereKey($warehouseId)->firstOrFail();
        Product::query()->whereKey($productId)->firstOrFail();
    }

    /**
     * Reserve stock for sales order confirmation.
     *
     * Caller must run inside DB::transaction() and process multiple lines in
     * CanonicalStockLockOrder to avoid deadlocks on overlapping products.
     */
    public function reserveQuantity(int $warehouseId, int $productId, int $quantity): void
    {
        $this->validateMovementQuantity($quantity);
        $this->assertWarehouseAndProductBelongToCurrentOrganization($warehouseId, $productId);

        $stock = $this->lockStockRow($warehouseId, $productId);

        $available = $stock->quantity_on_hand - $stock->quantity_reserved;

        if ($available < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['Insufficient available stock for this reservation.'],
            ]);
        }

        Stock::$quantityReservedUpdateFromService = true;

        try {
            $stock->quantity_reserved = $stock->quantity_reserved + $quantity;
            $stock->save();
        } finally {
            Stock::$quantityReservedUpdateFromService = false;
        }
    }

    /**
     * Release reservation and record a sale_out movement for order fulfillment.
     *
     * Caller must run inside DB::transaction(), lock the sales_orders row first,
     * and process multiple lines in CanonicalStockLockOrder.
     *
     * @param  array{
     *     note?: string|null,
     *     reference_type?: string|null,
     *     reference_id?: int|null,
     *     created_by: int,
     * }  $movementData
     */
    public function fulfillFromReservation(int $warehouseId, int $productId, int $quantity, array $movementData): StockMovement
    {
        $this->validateMovementQuantity($quantity);
        $this->assertWarehouseAndProductBelongToCurrentOrganization($warehouseId, $productId);

        $stock = $this->lockStockRow($warehouseId, $productId);

        if ($stock->quantity_reserved < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['Insufficient reserved stock for this fulfillment.'],
            ]);
        }

        if ($stock->quantity_on_hand < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['Insufficient on-hand stock for this fulfillment.'],
            ]);
        }

        Stock::$quantityReservedUpdateFromService = true;

        try {
            $stock->quantity_reserved = $stock->quantity_reserved - $quantity;
            $stock->save();
        } finally {
            Stock::$quantityReservedUpdateFromService = false;
        }

        return $this->recordMovement([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'type' => StockMovementType::SaleOut,
            'quantity' => $quantity,
            'note' => $movementData['note'] ?? null,
            'reference_type' => $movementData['reference_type'] ?? null,
            'reference_id' => $movementData['reference_id'] ?? null,
            'created_by' => (int) $movementData['created_by'],
        ]);
    }

    /**
     * Release a prior reservation (e.g. when cancelling a confirmed sales order).
     */
    public function releaseReservation(int $warehouseId, int $productId, int $quantity): void
    {
        $this->validateMovementQuantity($quantity);
        $this->assertWarehouseAndProductBelongToCurrentOrganization($warehouseId, $productId);

        $stock = $this->lockStockRow($warehouseId, $productId);

        $releaseAmount = min($quantity, $stock->quantity_reserved);

        if ($releaseAmount === 0) {
            return;
        }

        Stock::$quantityReservedUpdateFromService = true;

        try {
            $stock->quantity_reserved = max(0, $stock->quantity_reserved - $releaseAmount);
            $stock->save();
        } finally {
            Stock::$quantityReservedUpdateFromService = false;
        }
    }

    /**
     * Acquire an exclusive row lock on the stock record, creating a zero-quantity
     * placeholder when the warehouse/product pair is seen for the first time.
     *
     * Concurrent first-create race (two requests, no stocks row yet):
     * 1. Both call lockForUpdate(), both see no row.
     * 2. Both attempt INSERT; the unique (organization_id, warehouse_id, product_id)
     *    index makes the loser raise a QueryException.
     * 3. The loser catches that unique violation inside a nested transaction
     *    (savepoint), rolls back only the failed INSERT, and runs lockForUpdate()
     *    again — which now finds the winner's row.
     * 4. Both proceed to insert their stock_movements; the observer updates the
     *    same locked row. The user gets success, not a 500.
     */
    protected function lockStockRow(int $warehouseId, int $productId): Stock
    {
        $stock = Stock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($stock !== null) {
            return $stock;
        }

        try {
            DB::transaction(function () use ($warehouseId, $productId): void {
                Stock::query()->create([
                    'organization_id' => $this->currentOrganizationId(),
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                ]);
            });
        } catch (QueryException $exception) {
            if (! UniqueConstraintViolation::matches($exception)) {
                throw $exception;
            }
        }

        return Stock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    protected function currentOrganizationId(): int
    {
        if (! app()->bound('currentOrganization')) {
            throw new RuntimeException('Organization context is required for stock operations.');
        }

        return (int) app('currentOrganization')->id;
    }
}
