<?php

namespace App\Services;

use App\Enums\SalesOrderStatus;
use App\Models\SalesFulfillment;
use App\Models\SalesFulfillmentItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Support\CanonicalStockLockOrder;
use App\Support\OrderStatusNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesOrderFulfillmentService
{
    public function __construct(
        protected StockService $stockService,
    ) {}

    /**
     * Fulfill confirmed sales order lines, releasing reservations and writing sale_out movements.
     *
     * The sales_orders row is locked first so concurrent fulfill() calls for the
     * same order cannot both pass the confirmed status check before either commits.
     *
     * @param  array{
     *     items: list<array{sales_order_item_id: int, quantity: int}>,
     *     note?: string|null,
     * }  $data
     */
    public function fulfill(SalesOrder $salesOrder, array $data, int $fulfilledBy): SalesFulfillment
    {
        return DB::transaction(function () use ($salesOrder, $data, $fulfilledBy): SalesFulfillment {
            $salesOrder = SalesOrder::query()
                ->with('items')
                ->whereKey($salesOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $salesOrder->status->isFulfillable()) {
                throw ValidationException::withMessages([
                    'status' => ['Only confirmed sales orders can be fulfilled.'],
                ]);
            }

            $fulfillmentLines = $this->validateFulfillmentItems($salesOrder, $data['items'] ?? []);

            if ($fulfillmentLines === []) {
                throw ValidationException::withMessages([
                    'items' => ['At least one line item with a quantity greater than zero is required.'],
                ]);
            }

            $fulfillmentLines = CanonicalStockLockOrder::sortLinesByProductId(
                $fulfillmentLines,
                fn (array $line): int => $line['sales_order_item']->product_id,
            );

            $salesFulfillment = SalesFulfillment::query()->create([
                'sales_order_id' => $salesOrder->id,
                'fulfilled_by' => $fulfilledBy,
                'note' => $data['note'] ?? null,
                'fulfilled_at' => now(),
            ]);

            foreach ($fulfillmentLines as $line) {
                /** @var SalesOrderItem $orderItem */
                $orderItem = $line['sales_order_item'];
                $quantity = $line['quantity'];

                SalesFulfillmentItem::query()->create([
                    'sales_fulfillment_id' => $salesFulfillment->id,
                    'sales_order_item_id' => $orderItem->id,
                    'quantity_fulfilled' => $quantity,
                ]);

                $orderItem->increment('quantity_fulfilled', $quantity);

                $this->stockService->fulfillFromReservation(
                    $salesOrder->warehouse_id,
                    $orderItem->product_id,
                    $quantity,
                    [
                        'reference_type' => SalesFulfillment::class,
                        'reference_id' => $salesFulfillment->id,
                        'note' => $data['note'] ?? null,
                        'created_by' => $fulfilledBy,
                    ],
                );
            }

            $salesOrder->refresh();
            $salesOrder->load('items');
            $previousStatus = $salesOrder->status;
            $salesOrder->update([
                'status' => $this->resolveStatusAfterFulfillment($salesOrder),
            ]);
            OrderStatusNotifier::salesOrderChanged($salesOrder->fresh(), $previousStatus);

            return $salesFulfillment->fresh(['items.salesOrderItem.product', 'salesOrder']);
        });
    }

    /**
     * @param  list<array{sales_order_item_id: int, quantity: int}>  $items
     * @return list<array{sales_order_item: SalesOrderItem, quantity: int}>
     */
    protected function validateFulfillmentItems(SalesOrder $salesOrder, array $items): array
    {
        $orderItemsById = $salesOrder->items->keyBy('id');
        $fulfillmentLines = [];
        $seenItemIds = [];

        foreach ($items as $index => $item) {
            $orderItemId = (int) ($item['sales_order_item_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            if (in_array($orderItemId, $seenItemIds, true)) {
                throw ValidationException::withMessages([
                    "items.$index.sales_order_item_id" => ['Duplicate sales order line items are not allowed.'],
                ]);
            }

            /** @var SalesOrderItem|null $orderItem */
            $orderItem = $orderItemsById->get($orderItemId);

            if ($orderItem === null) {
                throw ValidationException::withMessages([
                    "items.$index.sales_order_item_id" => ['The selected sales order line item is invalid.'],
                ]);
            }

            $remaining = $orderItem->quantityRemainingToFulfill();

            if ($quantity > $remaining) {
                throw ValidationException::withMessages([
                    "items.$index.quantity" => ["Cannot fulfill more than the remaining ordered quantity ({$remaining})."],
                ]);
            }

            $seenItemIds[] = $orderItemId;
            $fulfillmentLines[] = [
                'sales_order_item' => $orderItem,
                'quantity' => $quantity,
            ];
        }

        return $fulfillmentLines;
    }

    protected function resolveStatusAfterFulfillment(SalesOrder $salesOrder): SalesOrderStatus
    {
        $allFulfilled = $salesOrder->items->every(
            fn (SalesOrderItem $item): bool => $item->quantity_fulfilled >= $item->quantity,
        );

        return $allFulfilled
            ? SalesOrderStatus::Shipped
            : SalesOrderStatus::Confirmed;
    }
}
