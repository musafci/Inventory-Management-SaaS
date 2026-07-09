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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends ApiController
{
    public function __construct(
        protected PurchaseOrderService $purchaseOrderService,
        protected GoodsReceiptService $goodsReceiptService,
        protected PaymentService $paymentService,
    ) {}

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

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $this->authorize('create', PurchaseOrder::class);

        $purchaseOrder = $this->purchaseOrderService->create($request->validated());

        return $this->success(new PurchaseOrderResource($purchaseOrder), status: 201);
    }

    public function show(int $purchaseOrderId): JsonResponse
    {
        $purchaseOrder = $this->findPurchaseOrderForCurrentOrganization($purchaseOrderId);

        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['supplier', 'warehouse', 'items.product']);

        return $this->success(new PurchaseOrderResource($purchaseOrder));
    }

    public function update(UpdatePurchaseOrderRequest $request, int $purchaseOrderId): JsonResponse
    {
        $purchaseOrder = $this->findPurchaseOrderForCurrentOrganization($purchaseOrderId);

        $this->authorize('update', $purchaseOrder);

        $purchaseOrder = $this->purchaseOrderService->update($purchaseOrder, $request->validated());

        return $this->success(new PurchaseOrderResource($purchaseOrder));
    }

    public function destroy(int $purchaseOrderId): Response
    {
        $purchaseOrder = $this->findPurchaseOrderForCurrentOrganization($purchaseOrderId);

        $this->authorize('delete', $purchaseOrder);

        $this->purchaseOrderService->delete($purchaseOrder);

        return response()->noContent();
    }

    public function send(int $purchaseOrderId): JsonResponse
    {
        $purchaseOrder = $this->findPurchaseOrderForCurrentOrganization($purchaseOrderId);

        $this->authorize('send', $purchaseOrder);

        $purchaseOrder = $this->purchaseOrderService->send($purchaseOrder);

        return $this->success(new PurchaseOrderResource($purchaseOrder));
    }

    public function cancel(int $purchaseOrderId): JsonResponse
    {
        $purchaseOrder = $this->findPurchaseOrderForCurrentOrganization($purchaseOrderId);

        $this->authorize('cancel', $purchaseOrder);

        $purchaseOrder = $this->purchaseOrderService->cancel($purchaseOrder);

        return $this->success(new PurchaseOrderResource($purchaseOrder));
    }

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
