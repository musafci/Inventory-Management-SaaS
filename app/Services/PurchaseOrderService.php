<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Support\UniqueConstraintViolation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PurchaseOrderService
{
    /**
     * @return LengthAwarePaginator<int, PurchaseOrder>
     */
    public function paginate(): LengthAwarePaginator
    {
        return QueryBuilder::for(PurchaseOrder::class)
            ->with(['supplier', 'warehouse', 'items.product'])
            ->allowedFilters(
                AllowedFilter::exact('supplier_id'),
                AllowedFilter::exact('warehouse_id'),
                AllowedFilter::exact('status'),
            )
            ->allowedSorts('order_date', 'po_number', 'created_at')
            ->defaultSort('-created_at')
            ->paginate(request()->integer('per_page', 15));
    }

    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data): PurchaseOrder {
            $this->assertSupplierBelongsToCurrentOrganization((int) $data['supplier_id']);
            $this->assertWarehouseBelongsToCurrentOrganization((int) $data['warehouse_id']);

            $items = $this->validateAndNormalizeItems($data['items']);

            $purchaseOrder = $this->insertPurchaseOrderWithUniqueNumber([
                'supplier_id' => (int) $data['supplier_id'],
                'warehouse_id' => (int) $data['warehouse_id'],
                'status' => PurchaseOrderStatus::Draft,
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'total_amount' => $this->calculateTotalAmount($items),
            ]);

            $this->syncItems($purchaseOrder, $items);

            return $purchaseOrder->fresh(['supplier', 'warehouse', 'items.product']);
        });
    }

    public function update(PurchaseOrder $purchaseOrder, array $data): PurchaseOrder
    {
        if (! $purchaseOrder->status->isEditable()) {
            throw ValidationException::withMessages([
                'status' => ['Only draft purchase orders can be updated.'],
            ]);
        }

        return DB::transaction(function () use ($purchaseOrder, $data): PurchaseOrder {
            if (isset($data['supplier_id'])) {
                $this->assertSupplierBelongsToCurrentOrganization((int) $data['supplier_id']);
            }

            if (isset($data['warehouse_id'])) {
                $this->assertWarehouseBelongsToCurrentOrganization((int) $data['warehouse_id']);
            }

            $purchaseOrder->fill(collect($data)->only([
                'supplier_id',
                'warehouse_id',
                'order_date',
                'expected_date',
            ])->filter(fn ($value) => $value !== null)->all());

            if (array_key_exists('items', $data)) {
                $items = $this->validateAndNormalizeItems($data['items']);
                $this->syncItems($purchaseOrder, $items);
                $purchaseOrder->total_amount = $this->calculateTotalAmount($items);
            }

            $purchaseOrder->save();

            return $purchaseOrder->fresh(['supplier', 'warehouse', 'items.product']);
        });
    }

    public function send(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        if ($purchaseOrder->status !== PurchaseOrderStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => ['Only draft purchase orders can be sent.'],
            ]);
        }

        if ($purchaseOrder->items()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => ['Purchase order must have at least one line item before sending.'],
            ]);
        }

        $purchaseOrder->update(['status' => PurchaseOrderStatus::Sent]);

        return $purchaseOrder->fresh(['supplier', 'warehouse', 'items.product']);
    }

    public function cancel(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        if (! $purchaseOrder->status->isCancellable()) {
            throw ValidationException::withMessages([
                'status' => ['This purchase order cannot be cancelled.'],
            ]);
        }

        $purchaseOrder->update(['status' => PurchaseOrderStatus::Cancelled]);

        return $purchaseOrder->fresh(['supplier', 'warehouse', 'items.product']);
    }

    public function delete(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->status !== PurchaseOrderStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => ['Only draft purchase orders can be deleted.'],
            ]);
        }

        $purchaseOrder->delete();
    }

    /**
     * @param  list<array{product_id: int, quantity_ordered: int, unit_cost: float|string}>  $items
     */
    protected function syncItems(PurchaseOrder $purchaseOrder, array $items): void
    {
        $purchaseOrder->items()->delete();

        foreach ($items as $item) {
            PurchaseOrderItem::query()->create([
                'purchase_order_id' => $purchaseOrder->id,
                'product_id' => $item['product_id'],
                'quantity_ordered' => $item['quantity_ordered'],
                'quantity_received' => 0,
                'unit_cost' => $item['unit_cost'],
                'subtotal' => $item['subtotal'],
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return list<array{product_id: int, quantity_ordered: int, unit_cost: string, subtotal: string}>
     */
    protected function validateAndNormalizeItems(array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => ['At least one line item is required.'],
            ]);
        }

        $normalized = [];
        $productIds = [];

        foreach ($items as $index => $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantityOrdered = (int) ($item['quantity_ordered'] ?? 0);
            $unitCost = round((float) ($item['unit_cost'] ?? 0), 2);

            if ($quantityOrdered <= 0) {
                throw ValidationException::withMessages([
                    "items.$index.quantity_ordered" => ['Quantity ordered must be greater than zero.'],
                ]);
            }

            if ($unitCost < 0) {
                throw ValidationException::withMessages([
                    "items.$index.unit_cost" => ['Unit cost cannot be negative.'],
                ]);
            }

            if (in_array($productId, $productIds, true)) {
                throw ValidationException::withMessages([
                    "items.$index.product_id" => ['Duplicate products are not allowed on a purchase order.'],
                ]);
            }

            Product::query()->whereKey($productId)->firstOrFail();
            $productIds[] = $productId;

            $normalized[] = [
                'product_id' => $productId,
                'quantity_ordered' => $quantityOrdered,
                'unit_cost' => number_format($unitCost, 2, '.', ''),
                'subtotal' => number_format($quantityOrdered * $unitCost, 2, '.', ''),
            ];
        }

        return $normalized;
    }

    /**
     * @param  list<array{subtotal: string}>  $items
     */
    protected function calculateTotalAmount(array $items): string
    {
        $total = array_reduce(
            $items,
            fn (float $carry, array $item): float => $carry + (float) $item['subtotal'],
            0.0,
        );

        return number_format($total, 2, '.', '');
    }

    /**
     * Insert a purchase order, retrying when concurrent creators collide on
     * the unique (organization_id, po_number) index — same pattern as lockStockRow().
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function insertPurchaseOrderWithUniqueNumber(array $attributes): PurchaseOrder
    {
        $maxAttempts = 10;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                return $this->attemptPurchaseOrderInsert($attributes);
            } catch (QueryException $exception) {
                if (! UniqueConstraintViolation::matches($exception)) {
                    throw $exception;
                }
            }
        }

        throw ValidationException::withMessages([
            'po_number' => ['Unable to allocate a unique purchase order number. Please retry.'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function attemptPurchaseOrderInsert(array $attributes): PurchaseOrder
    {
        try {
            return DB::transaction(function () use ($attributes): PurchaseOrder {
                return PurchaseOrder::query()->create([
                    ...$attributes,
                    'po_number' => $this->nextPoNumberCandidate(),
                ]);
            });
        } catch (QueryException $exception) {
            throw $exception;
        }
    }

    protected function nextPoNumberCandidate(): string
    {
        $latestPoNumber = PurchaseOrder::query()
            ->orderByDesc('id')
            ->value('po_number');

        $sequence = $latestPoNumber !== null ? (int) substr($latestPoNumber, 3) + 1 : 1;

        return sprintf('PO-%06d', $sequence);
    }

    protected function assertSupplierBelongsToCurrentOrganization(int $supplierId): void
    {
        Supplier::query()->whereKey($supplierId)->firstOrFail();
    }

    protected function assertWarehouseBelongsToCurrentOrganization(int $warehouseId): void
    {
        Warehouse::query()->whereKey($warehouseId)->firstOrFail();
    }
}
