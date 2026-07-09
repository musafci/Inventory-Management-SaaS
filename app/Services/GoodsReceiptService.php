<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Support\CanonicalStockLockOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoodsReceiptService
{
    public function __construct(
        protected StockService $stockService,
    ) {}

    /**
     * Receive goods against a purchase order, writing purchase_in stock movements.
     *
     * @param  array{
     *     items: list<array{purchase_order_item_id: int, quantity: int}>,
     *     note?: string|null,
     * }  $data
     */
    public function receive(PurchaseOrder $purchaseOrder, array $data, int $receivedBy): GoodsReceipt
    {
        return DB::transaction(function () use ($purchaseOrder, $data, $receivedBy): GoodsReceipt {
            $purchaseOrder = PurchaseOrder::query()
                ->with('items')
                ->whereKey($purchaseOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $purchaseOrder->status->isReceivable()) {
                throw ValidationException::withMessages([
                    'status' => ['Only sent or partially received purchase orders can be received against.'],
                ]);
            }

            $receiptLines = $this->validateReceiptItems($purchaseOrder, $data['items'] ?? []);

            if ($receiptLines === []) {
                throw ValidationException::withMessages([
                    'items' => ['At least one line item with a quantity greater than zero is required.'],
                ]);
            }

            $receiptLines = CanonicalStockLockOrder::sortLinesByProductId(
                $receiptLines,
                fn (array $line): int => $line['purchase_order_item']->product_id,
            );

            $goodsReceipt = GoodsReceipt::query()->create([
                'purchase_order_id' => $purchaseOrder->id,
                'received_by' => $receivedBy,
                'note' => $data['note'] ?? null,
                'received_at' => now(),
            ]);

            foreach ($receiptLines as $line) {
                /** @var PurchaseOrderItem $orderItem */
                $orderItem = $line['purchase_order_item'];
                $quantity = $line['quantity'];

                GoodsReceiptItem::query()->create([
                    'goods_receipt_id' => $goodsReceipt->id,
                    'purchase_order_item_id' => $orderItem->id,
                    'quantity_received' => $quantity,
                ]);

                $orderItem->increment('quantity_received', $quantity);

                $this->stockService->recordMovement([
                    'warehouse_id' => $purchaseOrder->warehouse_id,
                    'product_id' => $orderItem->product_id,
                    'type' => StockMovementType::PurchaseIn,
                    'quantity' => $quantity,
                    'reference_type' => GoodsReceipt::class,
                    'reference_id' => $goodsReceipt->id,
                    'note' => $data['note'] ?? null,
                    'created_by' => $receivedBy,
                ]);
            }

            $purchaseOrder->refresh();
            $purchaseOrder->load('items');
            $purchaseOrder->update([
                'status' => $this->resolveStatusAfterReceipt($purchaseOrder),
            ]);

            return $goodsReceipt->fresh(['items.purchaseOrderItem.product', 'purchaseOrder']);
        });
    }

    /**
     * @param  list<array{purchase_order_item_id: int, quantity: int}>  $items
     * @return list<array{purchase_order_item: PurchaseOrderItem, quantity: int}>
     */
    protected function validateReceiptItems(PurchaseOrder $purchaseOrder, array $items): array
    {
        $orderItemsById = $purchaseOrder->items->keyBy('id');
        $receiptLines = [];
        $seenItemIds = [];

        foreach ($items as $index => $item) {
            $orderItemId = (int) ($item['purchase_order_item_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            if (in_array($orderItemId, $seenItemIds, true)) {
                throw ValidationException::withMessages([
                    "items.$index.purchase_order_item_id" => ['Duplicate purchase order line items are not allowed.'],
                ]);
            }

            /** @var PurchaseOrderItem|null $orderItem */
            $orderItem = $orderItemsById->get($orderItemId);

            if ($orderItem === null) {
                throw ValidationException::withMessages([
                    "items.$index.purchase_order_item_id" => ['The selected purchase order line item is invalid.'],
                ]);
            }

            $remaining = $orderItem->quantityRemaining();

            if ($quantity > $remaining) {
                throw ValidationException::withMessages([
                    "items.$index.quantity" => ["Cannot receive more than the remaining ordered quantity ({$remaining})."],
                ]);
            }

            $seenItemIds[] = $orderItemId;
            $receiptLines[] = [
                'purchase_order_item' => $orderItem,
                'quantity' => $quantity,
            ];
        }

        return $receiptLines;
    }

    protected function resolveStatusAfterReceipt(PurchaseOrder $purchaseOrder): PurchaseOrderStatus
    {
        $allReceived = $purchaseOrder->items->every(
            fn (PurchaseOrderItem $item): bool => $item->quantity_received >= $item->quantity_ordered,
        );

        return $allReceived
            ? PurchaseOrderStatus::Received
            : PurchaseOrderStatus::PartiallyReceived;
    }
}
