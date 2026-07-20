<?php

namespace App\Services;

use App\Enums\SalesOrderStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use App\Support\CanonicalStockLockOrder;
use App\Support\OrderStatusNotifier;
use App\Support\UniqueConstraintViolation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SalesOrderService
{
    public function __construct(
        protected StockService $stockService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, SalesOrder>
     */
    public function paginate(): LengthAwarePaginator
    {
        return QueryBuilder::for(SalesOrder::class)
            ->with(['customer', 'warehouse', 'items.product'])
            ->allowedFilters(
                AllowedFilter::exact('customer_id'),
                AllowedFilter::exact('warehouse_id'),
                AllowedFilter::exact('status'),
            )
            ->allowedSorts('order_date', 'order_number', 'created_at')
            ->defaultSort('-created_at')
            ->paginate(request()->integer('per_page', 15));
    }

    public function create(array $data): SalesOrder
    {
        return DB::transaction(function () use ($data): SalesOrder {
            $this->assertCustomerBelongsToCurrentOrganization((int) $data['customer_id']);
            $this->assertWarehouseBelongsToCurrentOrganization((int) $data['warehouse_id']);

            $items = $this->validateAndNormalizeItems($data['items']);

            $salesOrder = $this->insertSalesOrderWithUniqueNumber([
                'customer_id' => (int) $data['customer_id'],
                'warehouse_id' => (int) $data['warehouse_id'],
                'status' => SalesOrderStatus::Draft,
                'order_date' => $data['order_date'],
                'total_amount' => $this->calculateTotalAmount($items),
            ]);

            $this->syncItems($salesOrder, $items);

            return $salesOrder->fresh(['customer', 'warehouse', 'items.product']);
        });
    }

    public function update(SalesOrder $salesOrder, array $data): SalesOrder
    {
        if (! $salesOrder->status->isEditable()) {
            throw ValidationException::withMessages([
                'status' => ['Only draft sales orders can be updated.'],
            ]);
        }

        return DB::transaction(function () use ($salesOrder, $data): SalesOrder {
            if (isset($data['customer_id'])) {
                $this->assertCustomerBelongsToCurrentOrganization((int) $data['customer_id']);
            }

            if (isset($data['warehouse_id'])) {
                $this->assertWarehouseBelongsToCurrentOrganization((int) $data['warehouse_id']);
            }

            $salesOrder->fill(collect($data)->only([
                'customer_id',
                'warehouse_id',
                'order_date',
            ])->filter(fn ($value) => $value !== null)->all());

            if (array_key_exists('items', $data)) {
                $items = $this->validateAndNormalizeItems($data['items']);
                $this->syncItems($salesOrder, $items);
                $salesOrder->total_amount = $this->calculateTotalAmount($items);
            }

            $salesOrder->save();

            return $salesOrder->fresh(['customer', 'warehouse', 'items.product']);
        });
    }

    /**
     * Confirm a draft sales order, reserving stock under row locks.
     *
     * The sales_orders row is locked first so concurrent confirm() calls for the
     * same order cannot both pass the draft status check before either commits.
     */
    public function confirm(SalesOrder $salesOrder): SalesOrder
    {
        return DB::transaction(function () use ($salesOrder): SalesOrder {
            $salesOrder = SalesOrder::query()
                ->with('items')
                ->whereKey($salesOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $salesOrder->status->isConfirmable()) {
                throw ValidationException::withMessages([
                    'status' => ['Only draft sales orders can be confirmed.'],
                ]);
            }

            if ($salesOrder->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => ['Sales order must have at least one line item before confirmation.'],
                ]);
            }

            $sortedItems = CanonicalStockLockOrder::sortLinesByProductId(
                $salesOrder->items->all(),
                fn (SalesOrderItem $item): int => $item->product_id,
            );

            foreach ($sortedItems as $item) {
                $this->stockService->reserveQuantity(
                    $salesOrder->warehouse_id,
                    $item->product_id,
                    $item->quantity,
                );
            }

            $previousStatus = $salesOrder->status;
            $salesOrder->update(['status' => SalesOrderStatus::Confirmed]);
            OrderStatusNotifier::salesOrderChanged($salesOrder->fresh(), $previousStatus);

            return $salesOrder->fresh(['customer', 'warehouse', 'items.product']);
        });
    }

    public function cancel(SalesOrder $salesOrder): SalesOrder
    {
        return DB::transaction(function () use ($salesOrder): SalesOrder {
            $salesOrder = SalesOrder::query()
                ->with('items')
                ->whereKey($salesOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $salesOrder->status->isCancellable()) {
                throw ValidationException::withMessages([
                    'status' => ['This sales order cannot be cancelled.'],
                ]);
            }

            if ((float) $salesOrder->netAmountPaid() > 0) {
                throw ValidationException::withMessages([
                    'status' => ['Cannot cancel a sales order with recorded payments. Refund all payments first.'],
                ]);
            }

            if ($salesOrder->status->hasActiveReservation()) {
                $sortedItems = CanonicalStockLockOrder::sortLinesByProductId(
                    $salesOrder->items->all(),
                    fn (SalesOrderItem $item): int => $item->product_id,
                );

                foreach ($sortedItems as $item) {
                    $remainingReserved = $item->quantityRemainingToFulfill();

                    if ($remainingReserved > 0) {
                        $this->stockService->releaseReservation(
                            $salesOrder->warehouse_id,
                            $item->product_id,
                            $remainingReserved,
                        );
                    }
                }
            }

            $previousStatus = $salesOrder->status;
            $salesOrder->update(['status' => SalesOrderStatus::Cancelled]);
            OrderStatusNotifier::salesOrderChanged($salesOrder->fresh(), $previousStatus);

            return $salesOrder->fresh(['customer', 'warehouse', 'items.product']);
        });
    }

    /**
     * Mark a shipped sales order as physically delivered.
     *
     * Delivery is independent of payment — an order can be delivered on credit
     * terms before any payment is recorded, or prepaid before it ships.
     */
    public function deliver(SalesOrder $salesOrder): SalesOrder
    {
        return DB::transaction(function () use ($salesOrder): SalesOrder {
            $salesOrder = SalesOrder::query()
                ->whereKey($salesOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $salesOrder->status->isDeliverable()) {
                throw ValidationException::withMessages([
                    'status' => ['Only shipped sales orders can be marked as delivered.'],
                ]);
            }

            $previousStatus = $salesOrder->status;
            $salesOrder->update(['status' => SalesOrderStatus::Delivered]);
            OrderStatusNotifier::salesOrderChanged($salesOrder->fresh(), $previousStatus);

            return $salesOrder->fresh(['customer', 'warehouse', 'items.product']);
        });
    }

    public function delete(SalesOrder $salesOrder): void
    {
        if (! $salesOrder->status->isEditable()) {
            throw ValidationException::withMessages([
                'status' => ['Only draft sales orders can be deleted.'],
            ]);
        }

        $salesOrder->delete();
    }

    /**
     * @param  list<array{product_id: int, quantity: int, unit_price: float|string, discount?: float|string}>  $items
     */
    protected function syncItems(SalesOrder $salesOrder, array $items): void
    {
        $salesOrder->items()->delete();

        foreach ($items as $item) {
            SalesOrderItem::query()->create([
                'sales_order_id' => $salesOrder->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount' => $item['discount'],
                'subtotal' => $item['subtotal'],
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return list<array{product_id: int, quantity: int, unit_price: string, discount: string, subtotal: string}>
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
            $quantity = (int) ($item['quantity'] ?? 0);
            $unitPrice = round((float) ($item['unit_price'] ?? 0), 2);
            $discount = round((float) ($item['discount'] ?? 0), 2);

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    "items.$index.quantity" => ['Quantity must be greater than zero.'],
                ]);
            }

            if ($unitPrice < 0) {
                throw ValidationException::withMessages([
                    "items.$index.unit_price" => ['Unit price cannot be negative.'],
                ]);
            }

            if ($discount < 0) {
                throw ValidationException::withMessages([
                    "items.$index.discount" => ['Discount cannot be negative.'],
                ]);
            }

            if (in_array($productId, $productIds, true)) {
                throw ValidationException::withMessages([
                    "items.$index.product_id" => ['Duplicate products are not allowed on a sales order.'],
                ]);
            }

            Product::query()->whereKey($productId)->firstOrFail();
            $productIds[] = $productId;

            $normalized[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => number_format($unitPrice, 2, '.', ''),
                'discount' => number_format($discount, 2, '.', ''),
                'subtotal' => number_format(($quantity * $unitPrice) - $discount, 2, '.', ''),
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
     * @param  array<string, mixed>  $attributes
     */
    protected function insertSalesOrderWithUniqueNumber(array $attributes): SalesOrder
    {
        $maxAttempts = 10;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                return $this->attemptSalesOrderInsert($attributes);
            } catch (QueryException $exception) {
                if (! UniqueConstraintViolation::matches($exception)) {
                    throw $exception;
                }
            }
        }

        throw ValidationException::withMessages([
            'order_number' => ['Unable to allocate a unique sales order number. Please retry.'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function attemptSalesOrderInsert(array $attributes): SalesOrder
    {
        return DB::transaction(function () use ($attributes): SalesOrder {
            return SalesOrder::query()->create([
                ...$attributes,
                'order_number' => $this->nextOrderNumberCandidate(),
            ]);
        });
    }

    protected function nextOrderNumberCandidate(): string
    {
        $latestOrderNumber = SalesOrder::query()
            ->orderByDesc('id')
            ->value('order_number');

        $sequence = $latestOrderNumber !== null ? (int) substr($latestOrderNumber, 3) + 1 : 1;

        return sprintf('SO-%06d', $sequence);
    }

    protected function assertCustomerBelongsToCurrentOrganization(int $customerId): void
    {
        Customer::query()->whereKey($customerId)->firstOrFail();
    }

    protected function assertWarehouseBelongsToCurrentOrganization(int $warehouseId): void
    {
        Warehouse::query()->whereKey($warehouseId)->firstOrFail();
    }
}
