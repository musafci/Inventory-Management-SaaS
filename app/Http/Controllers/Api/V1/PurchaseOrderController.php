<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\PurchaseOrder\ReceivePurchaseOrderRequest;
use App\Http\Requests\Payment\RecordPaymentRequest;
use App\Http\Requests\PurchaseOrder\StorePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\UpdatePurchaseOrderRequest;
use App\Http\Resources\GoodsReceiptResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Services\GoodsReceiptService;
use App\Services\PaymentService;
use App\Services\PurchaseOrderService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

#[Group('Purchase Orders', description: 'Purchase order lifecycle: draft, send, receive goods, record payments, and cancel.', weight: 11)]
class PurchaseOrderController extends ApiController
{
    public function __construct(
        protected PurchaseOrderService $purchaseOrderService,
        protected GoodsReceiptService $goodsReceiptService,
        protected PaymentService $paymentService,
    ) {}

    #[Endpoint(operationId: 'purchase-orders.index', title: 'List purchase orders', description: 'Returns a paginated list of purchase orders for the active organization.')]
    #[ApiResponse(
        status: 200,
        description: 'Paginated purchase order list.',
        examples: [[
            'data' => [[
                'id' => 1,
                'organization_id' => 1,
                'supplier_id' => 1,
                'warehouse_id' => 1,
                'po_number' => 'PO-00001',
                'status' => 'draft',
                'order_date' => '2026-07-09',
                'expected_date' => '2026-07-16',
                'total_amount' => '100.00',
                'amount_paid' => '0.00',
                'amount_due' => '100.00',
                'created_at' => '2026-07-10T12:00:00.000000Z',
                'updated_at' => '2026-07-10T12:00:00.000000Z',
            ]],
            'meta' => [
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 15,
                    'total' => 1,
                    'last_page' => 1,
                ],
            ],
        ]],
    )]
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $purchaseOrders = $this->purchaseOrderService->paginate();

        return $this->success(
            PurchaseOrderResource::collection($purchaseOrders->items()),
            [
                'pagination' => [
                    'current_page' => $purchaseOrders->currentPage(),
                    'per_page' => $purchaseOrders->perPage(),
                    'total' => $purchaseOrders->total(),
                    'last_page' => $purchaseOrders->lastPage(),
                ],
            ],
        );
    }

    #[Endpoint(operationId: 'purchase-orders.store', title: 'Create purchase order', description: 'Creates a draft purchase order. Requires an `Idempotency-Key` header.')]
    #[ApiResponse(
        status: 201,
        description: 'Draft purchase order created.',
        examples: [[
            'data' => [
                'id' => 1,
                'organization_id' => 1,
                'supplier_id' => 1,
                'warehouse_id' => 1,
                'po_number' => 'PO-00001',
                'status' => 'draft',
                'order_date' => '2026-07-09',
                'expected_date' => '2026-07-16',
                'total_amount' => '100.00',
                'amount_paid' => '0.00',
                'amount_due' => '100.00',
                'created_at' => '2026-07-10T12:00:00.000000Z',
                'updated_at' => '2026-07-10T12:00:00.000000Z',
            ],
        ]],
    )]
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $this->authorize('create', PurchaseOrder::class);

        $purchaseOrder = $this->purchaseOrderService->create($request->validated());

        return $this->success(new PurchaseOrderResource($purchaseOrder), status: 201);
    }

    #[Endpoint(operationId: 'purchase-orders.show', title: 'Show purchase order', description: 'Returns a purchase order with supplier, warehouse, and line items.')]
    #[ApiResponse(
        status: 200,
        description: 'Purchase order with related supplier, warehouse, and items.',
        examples: [[
            'data' => [
                'id' => 1,
                'organization_id' => 1,
                'supplier_id' => 1,
                'warehouse_id' => 1,
                'po_number' => 'PO-00001',
                'status' => 'sent',
                'order_date' => '2026-07-09',
                'expected_date' => '2026-07-16',
                'total_amount' => '100.00',
                'amount_paid' => '0.00',
                'amount_due' => '100.00',
                'supplier' => [
                    'id' => 1,
                    'name' => 'Acme Supplies',
                ],
                'warehouse' => [
                    'id' => 1,
                    'name' => 'Receiving Warehouse',
                ],
                'items' => [[
                    'id' => 1,
                    'product_id' => 1,
                    'quantity_ordered' => 20,
                    'quantity_received' => 0,
                    'quantity_remaining' => 20,
                    'unit_cost' => '5.00',
                    'subtotal' => '100.00',
                ]],
                'created_at' => '2026-07-10T12:00:00.000000Z',
                'updated_at' => '2026-07-10T12:30:00.000000Z',
            ],
        ]],
    )]
    public function show(int $purchaseOrderId): JsonResponse
    {
        $purchaseOrder = $this->findPurchaseOrderForCurrentOrganization($purchaseOrderId);

        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['supplier', 'warehouse', 'items.product']);

        return $this->success(new PurchaseOrderResource($purchaseOrder));
    }

    #[Endpoint(operationId: 'purchase-orders.update', title: 'Update purchase order', description: 'Updates a draft purchase order. Only editable while status is `draft`.')]
    #[ApiResponse(
        status: 200,
        description: 'Purchase order updated.',
        examples: [[
            'data' => [
                'id' => 1,
                'organization_id' => 1,
                'supplier_id' => 1,
                'warehouse_id' => 1,
                'po_number' => 'PO-00001',
                'status' => 'draft',
                'order_date' => '2026-07-09',
                'expected_date' => '2026-07-20',
                'total_amount' => '150.00',
                'amount_paid' => '0.00',
                'amount_due' => '150.00',
                'created_at' => '2026-07-10T12:00:00.000000Z',
                'updated_at' => '2026-07-10T13:00:00.000000Z',
            ],
        ]],
    )]
    public function update(UpdatePurchaseOrderRequest $request, int $purchaseOrderId): JsonResponse
    {
        $purchaseOrder = $this->findPurchaseOrderForCurrentOrganization($purchaseOrderId);

        $this->authorize('update', $purchaseOrder);

        $purchaseOrder = $this->purchaseOrderService->update($purchaseOrder, $request->validated());

        return $this->success(new PurchaseOrderResource($purchaseOrder));
    }

    #[Endpoint(operationId: 'purchase-orders.destroy', title: 'Delete purchase order', description: 'Deletes a draft purchase order.')]
    #[ApiResponse(status: 204, description: 'Purchase order deleted.')]
    public function destroy(int $purchaseOrderId): Response
    {
        $purchaseOrder = $this->findPurchaseOrderForCurrentOrganization($purchaseOrderId);

        $this->authorize('delete', $purchaseOrder);

        $this->purchaseOrderService->delete($purchaseOrder);

        return response()->noContent();
    }

    #[Endpoint(operationId: 'purchase-orders.send', title: 'Send purchase order', description: 'Transitions a draft purchase order to `sent` status.')]
    #[ApiResponse(
        status: 200,
        description: 'Purchase order sent to supplier.',
        examples: [[
            'data' => [
                'id' => 1,
                'organization_id' => 1,
                'supplier_id' => 1,
                'warehouse_id' => 1,
                'po_number' => 'PO-00001',
                'status' => 'sent',
                'order_date' => '2026-07-09',
                'expected_date' => '2026-07-16',
                'total_amount' => '100.00',
                'amount_paid' => '0.00',
                'amount_due' => '100.00',
                'created_at' => '2026-07-10T12:00:00.000000Z',
                'updated_at' => '2026-07-10T12:30:00.000000Z',
            ],
        ]],
    )]
    public function send(int $purchaseOrderId): JsonResponse
    {
        $purchaseOrder = $this->findPurchaseOrderForCurrentOrganization($purchaseOrderId);

        $this->authorize('send', $purchaseOrder);

        $purchaseOrder = $this->purchaseOrderService->send($purchaseOrder);

        return $this->success(new PurchaseOrderResource($purchaseOrder));
    }

    #[Endpoint(operationId: 'purchase-orders.cancel', title: 'Cancel purchase order', description: 'Cancels a draft or sent purchase order.')]
    #[ApiResponse(
        status: 200,
        description: 'Purchase order cancelled.',
        examples: [[
            'data' => [
                'id' => 1,
                'organization_id' => 1,
                'supplier_id' => 1,
                'warehouse_id' => 1,
                'po_number' => 'PO-00001',
                'status' => 'cancelled',
                'order_date' => '2026-07-09',
                'expected_date' => '2026-07-16',
                'total_amount' => '100.00',
                'amount_paid' => '0.00',
                'amount_due' => '100.00',
                'created_at' => '2026-07-10T12:00:00.000000Z',
                'updated_at' => '2026-07-10T14:00:00.000000Z',
            ],
        ]],
    )]
    public function cancel(int $purchaseOrderId): JsonResponse
    {
        $purchaseOrder = $this->findPurchaseOrderForCurrentOrganization($purchaseOrderId);

        $this->authorize('cancel', $purchaseOrder);

        $purchaseOrder = $this->purchaseOrderService->cancel($purchaseOrder);

        return $this->success(new PurchaseOrderResource($purchaseOrder));
    }

    #[Endpoint(operationId: 'purchase-orders.receive', title: 'Receive goods', description: 'Records a goods receipt against a sent or partially received purchase order and posts `purchase_in` stock movements.')]
    #[ApiResponse(
        status: 201,
        description: 'Goods receipt recorded and stock updated.',
        examples: [[
            'data' => [
                'id' => 1,
                'organization_id' => 1,
                'purchase_order_id' => 1,
                'received_by' => 1,
                'note' => 'First delivery',
                'received_at' => '2026-07-10T15:00:00.000000Z',
                'items' => [[
                    'id' => 1,
                    'purchase_order_item_id' => 1,
                    'quantity_received' => 10,
                ]],
                'created_at' => '2026-07-10T15:00:00.000000Z',
                'updated_at' => '2026-07-10T15:00:00.000000Z',
            ],
        ]],
    )]
    public function receive(ReceivePurchaseOrderRequest $request, int $purchaseOrderId): JsonResponse
    {
        $purchaseOrder = $this->findPurchaseOrderForCurrentOrganization($purchaseOrderId);

        $this->authorize('receive', $purchaseOrder);

        $goodsReceipt = $this->goodsReceiptService->receive(
            $purchaseOrder,
            $request->validated(),
            (int) Auth::id(),
        );

        return $this->success(new GoodsReceiptResource($goodsReceipt), status: 201);
    }

    #[Endpoint(operationId: 'purchase-orders.pay', title: 'Record purchase payment', description: 'Records a payment against a partially received or fully received purchase order.')]
    #[ApiResponse(
        status: 201,
        description: 'Payment recorded.',
        examples: [[
            'data' => [
                'id' => 1,
                'organization_id' => 1,
                'payable_type' => 'App\\Models\\PurchaseOrder',
                'payable_id' => 1,
                'amount' => '50.00',
                'method' => 'bank_transfer',
                'status' => 'completed',
                'reference' => 'TXN-12345',
                'note' => 'Partial payment',
                'recorded_by' => 1,
                'paid_at' => '2026-07-10T16:00:00.000000Z',
                'created_at' => '2026-07-10T16:00:00.000000Z',
                'updated_at' => '2026-07-10T16:00:00.000000Z',
            ],
        ]],
    )]
    public function pay(RecordPaymentRequest $request, int $purchaseOrderId): JsonResponse
    {
        $purchaseOrder = $this->findPurchaseOrderForCurrentOrganization($purchaseOrderId);

        $this->authorize('pay', $purchaseOrder);

        $payment = $this->paymentService->recordPurchasePayment(
            $purchaseOrder,
            $request->validated(),
            (int) Auth::id(),
        );

        return $this->success(new PaymentResource($payment), status: 201);
    }

    protected function findPurchaseOrderForCurrentOrganization(int $purchaseOrderId): PurchaseOrder
    {
        return PurchaseOrder::query()
            ->whereKey($purchaseOrderId)
            ->where('organization_id', app('currentOrganization')->id)
            ->firstOrFail();
    }
}
