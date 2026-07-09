<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SalesOrderStatus;
use App\Enums\StockMovementType;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Support\CanonicalStockLockOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PaymentService
{
    public function __construct(
        protected StockService $stockService,
    ) {}
    /**
     * @return LengthAwarePaginator<int, Payment>
     */
    public function paginate(): LengthAwarePaginator
    {
        return QueryBuilder::for(Payment::class)
            ->with(['payable', 'recordedBy'])
            ->allowedFilters(
                AllowedFilter::exact('payable_type'),
                AllowedFilter::exact('payable_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('method'),
            )
            ->allowedSorts('paid_at', 'amount', 'created_at')
            ->defaultSort('-paid_at')
            ->paginate(request()->integer('per_page', 15));
    }

    /**
     * @param  array{
     *     amount: float|string,
     *     method: PaymentMethod|string,
     *     reference?: string|null,
     *     note?: string|null,
     *     paid_at?: string|null,
     * }  $data
     */
    public function recordSalesPayment(SalesOrder $salesOrder, array $data, int $recordedBy): Payment
    {
        return DB::transaction(function () use ($salesOrder, $data, $recordedBy): Payment {
            $salesOrder = SalesOrder::query()
                ->whereKey($salesOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $salesOrder->status->isPayable()) {
                throw ValidationException::withMessages([
                    'status' => ['Only confirmed, shipped, or delivered sales orders can receive payments.'],
                ]);
            }

            $amount = $this->normalizeAmount($data['amount']);
            $this->assertPositiveAmount($amount);

            $netPaid = (float) $salesOrder->netAmountPaid();

            if ($netPaid + $amount > (float) $salesOrder->total_amount) {
                throw ValidationException::withMessages([
                    'amount' => ['Cannot overpay the sales order.'],
                ]);
            }

            $payment = Payment::query()->create([
                'payable_type' => SalesOrder::class,
                'payable_id' => $salesOrder->id,
                'amount' => number_format($amount, 2, '.', ''),
                'method' => $this->resolveMethod($data['method']),
                'status' => PaymentStatus::Completed,
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
                'recorded_by' => $recordedBy,
                'paid_at' => $data['paid_at'] ?? now(),
            ]);

            return $payment->fresh(['payable', 'recordedBy']);
        });
    }

    /**
     * Refund a shipped or delivered sales order, optionally restocking returned goods.
     *
     * The sales_orders row is locked first so concurrent refund() calls for the
     * same order cannot both pass returnability / over-refund checks before either
     * commits. Return-item validation, payment insert, quantity_returned updates,
     * and return_in movements all run inside this single transaction.
     *
     * @param  array{
     *     amount: float|string,
     *     method: PaymentMethod|string,
     *     reference?: string|null,
     *     note?: string|null,
     *     paid_at?: string|null,
     *     return_items?: list<array{sales_order_item_id: int, quantity: int}>,
     * }  $data
     */
    public function recordSalesRefund(SalesOrder $salesOrder, array $data, int $recordedBy): Payment
    {
        return DB::transaction(function () use ($salesOrder, $data, $recordedBy): Payment {
            $salesOrder = SalesOrder::query()
                ->with('items')
                ->whereKey($salesOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $salesOrder->status->isRefundable()) {
                throw ValidationException::withMessages([
                    'status' => ['Only shipped or delivered sales orders can be refunded.'],
                ]);
            }

            $amount = $this->normalizeAmount($data['amount']);
            $this->assertPositiveAmount($amount);

            $netPaid = (float) $salesOrder->netAmountPaid();

            if ($amount > $netPaid) {
                throw ValidationException::withMessages([
                    'amount' => ['Cannot refund more than the net amount paid.'],
                ]);
            }

            $returnLines = $this->validateReturnItems($salesOrder, $data['return_items'] ?? []);

            $payment = Payment::query()->create([
                'payable_type' => SalesOrder::class,
                'payable_id' => $salesOrder->id,
                'amount' => number_format($amount, 2, '.', ''),
                'method' => $this->resolveMethod($data['method']),
                'status' => PaymentStatus::Refunded,
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
                'recorded_by' => $recordedBy,
                'paid_at' => $data['paid_at'] ?? now(),
            ]);

            if ($returnLines !== []) {
                $returnLines = CanonicalStockLockOrder::sortLinesByProductId(
                    $returnLines,
                    fn (array $line): int => $line['sales_order_item']->product_id,
                );

                foreach ($returnLines as $line) {
                    /** @var SalesOrderItem $orderItem */
                    $orderItem = $line['sales_order_item'];
                    $quantity = $line['quantity'];

                    $orderItem->increment('quantity_returned', $quantity);

                    $this->stockService->recordMovement([
                        'warehouse_id' => $salesOrder->warehouse_id,
                        'product_id' => $orderItem->product_id,
                        'type' => StockMovementType::ReturnIn,
                        'quantity' => $quantity,
                        'reference_type' => Payment::class,
                        'reference_id' => $payment->id,
                        'note' => $data['note'] ?? null,
                        'created_by' => $recordedBy,
                    ]);
                }
            }

            $remainingPaid = $netPaid - $amount;

            $salesOrder->update([
                'status' => $remainingPaid <= 0
                    ? SalesOrderStatus::Refunded
                    : $salesOrder->status,
            ]);

            return $payment->fresh(['payable', 'recordedBy']);
        });
    }

    /**
     * @param  list<array{sales_order_item_id: int, quantity: int}>  $items
     * @return list<array{sales_order_item: SalesOrderItem, quantity: int}>
     */
    protected function validateReturnItems(SalesOrder $salesOrder, array $items): array
    {
        if ($items === []) {
            return [];
        }

        $orderItemsById = $salesOrder->items->keyBy('id');
        $returnLines = [];
        $seenItemIds = [];

        foreach ($items as $index => $item) {
            $orderItemId = (int) ($item['sales_order_item_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            if (in_array($orderItemId, $seenItemIds, true)) {
                throw ValidationException::withMessages([
                    "return_items.$index.sales_order_item_id" => ['Duplicate sales order line items are not allowed.'],
                ]);
            }

            /** @var SalesOrderItem|null $orderItem */
            $orderItem = $orderItemsById->get($orderItemId);

            if ($orderItem === null) {
                throw ValidationException::withMessages([
                    "return_items.$index.sales_order_item_id" => ['The selected sales order line item is invalid.'],
                ]);
            }

            $remaining = $orderItem->quantityRemainingToReturn();

            if ($quantity > $remaining) {
                throw ValidationException::withMessages([
                    "return_items.$index.quantity" => ["Cannot return more than the remaining fulfilled quantity ({$remaining})."],
                ]);
            }

            $seenItemIds[] = $orderItemId;
            $returnLines[] = [
                'sales_order_item' => $orderItem,
                'quantity' => $quantity,
            ];
        }

        return $returnLines;
    }

    /**
     * @param  array{
     *     amount: float|string,
     *     method: PaymentMethod|string,
     *     reference?: string|null,
     *     note?: string|null,
     *     paid_at?: string|null,
     * }  $data
     */
    public function recordPurchasePayment(PurchaseOrder $purchaseOrder, array $data, int $recordedBy): Payment
    {
        return DB::transaction(function () use ($purchaseOrder, $data, $recordedBy): Payment {
            $purchaseOrder = PurchaseOrder::query()
                ->whereKey($purchaseOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $purchaseOrder->status->isPayable()) {
                throw ValidationException::withMessages([
                    'status' => ['Only partially received or received purchase orders can be paid against.'],
                ]);
            }

            $amount = $this->normalizeAmount($data['amount']);
            $this->assertPositiveAmount($amount);

            $netPaid = (float) $purchaseOrder->netAmountPaid();

            if ($netPaid + $amount > (float) $purchaseOrder->total_amount) {
                throw ValidationException::withMessages([
                    'amount' => ['Cannot overpay the purchase order.'],
                ]);
            }

            return Payment::query()->create([
                'payable_type' => PurchaseOrder::class,
                'payable_id' => $purchaseOrder->id,
                'amount' => number_format($amount, 2, '.', ''),
                'method' => $this->resolveMethod($data['method']),
                'status' => PaymentStatus::Completed,
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
                'recorded_by' => $recordedBy,
                'paid_at' => $data['paid_at'] ?? now(),
            ])->fresh(['payable', 'recordedBy']);
        });
    }

    protected function normalizeAmount(float|string $amount): float
    {
        return round((float) $amount, 2);
    }

    protected function assertPositiveAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Amount must be greater than zero.'],
            ]);
        }
    }

    protected function resolveMethod(PaymentMethod|string $method): PaymentMethod
    {
        return $method instanceof PaymentMethod
            ? $method
            : PaymentMethod::from($method);
    }
}
