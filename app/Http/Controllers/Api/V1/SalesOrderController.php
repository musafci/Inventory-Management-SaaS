<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\SalesOrder\FulfillSalesOrderRequest;
use App\Http\Requests\Payment\RecordPaymentRequest;
use App\Http\Requests\Payment\RecordRefundRequest;
use App\Http\Requests\SalesOrder\StoreSalesOrderRequest;
use App\Http\Requests\SalesOrder\UpdateSalesOrderRequest;
use App\Http\Resources\SalesFulfillmentResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\SalesOrderResource;
use App\Models\SalesOrder;
use App\Services\PaymentService;
use App\Services\SalesOrderFulfillmentService;
use App\Services\SalesOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SalesOrderController extends ApiController
{
    public function __construct(
        protected SalesOrderService $salesOrderService,
        protected SalesOrderFulfillmentService $salesOrderFulfillmentService,
        protected PaymentService $paymentService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', SalesOrder::class);

        $salesOrders = $this->salesOrderService->paginate();

        return $this->success(
            SalesOrderResource::collection($salesOrders->items()),
            [
                'pagination' => [
                    'current_page' => $salesOrders->currentPage(),
                    'per_page' => $salesOrders->perPage(),
                    'total' => $salesOrders->total(),
                    'last_page' => $salesOrders->lastPage(),
                ],
            ],
        );
    }

    public function store(StoreSalesOrderRequest $request): JsonResponse
    {
        $this->authorize('create', SalesOrder::class);

        $salesOrder = $this->salesOrderService->create($request->validated());

        return $this->success(new SalesOrderResource($salesOrder), status: 201);
    }

    public function show(int $salesOrderId): JsonResponse
    {
        $salesOrder = $this->findSalesOrderForCurrentOrganization($salesOrderId);

        $this->authorize('view', $salesOrder);

        $salesOrder->load(['customer', 'warehouse', 'items.product']);

        return $this->success(new SalesOrderResource($salesOrder));
    }

    public function update(UpdateSalesOrderRequest $request, int $salesOrderId): JsonResponse
    {
        $salesOrder = $this->findSalesOrderForCurrentOrganization($salesOrderId);

        $this->authorize('update', $salesOrder);

        $salesOrder = $this->salesOrderService->update($salesOrder, $request->validated());

        return $this->success(new SalesOrderResource($salesOrder));
    }

    public function destroy(int $salesOrderId): Response
    {
        $salesOrder = $this->findSalesOrderForCurrentOrganization($salesOrderId);

        $this->authorize('delete', $salesOrder);

        $this->salesOrderService->delete($salesOrder);

        return response()->noContent();
    }

    public function confirm(int $salesOrderId): JsonResponse
    {
        $salesOrder = $this->findSalesOrderForCurrentOrganization($salesOrderId);

        $this->authorize('confirm', $salesOrder);

        $salesOrder = $this->salesOrderService->confirm($salesOrder);

        return $this->success(new SalesOrderResource($salesOrder));
    }

    public function cancel(int $salesOrderId): JsonResponse
    {
        $salesOrder = $this->findSalesOrderForCurrentOrganization($salesOrderId);

        $this->authorize('cancel', $salesOrder);

        $salesOrder = $this->salesOrderService->cancel($salesOrder);

        return $this->success(new SalesOrderResource($salesOrder));
    }

    public function fulfill(FulfillSalesOrderRequest $request, int $salesOrderId): JsonResponse
    {
        $salesOrder = $this->findSalesOrderForCurrentOrganization($salesOrderId);

        $this->authorize('fulfill', $salesOrder);

        $salesFulfillment = $this->salesOrderFulfillmentService->fulfill(
            $salesOrder,
            $request->validated(),
            (int) $request->user()->id,
        );

        return $this->success(new SalesFulfillmentResource($salesFulfillment), status: 201);
    }

    public function pay(RecordPaymentRequest $request, int $salesOrderId): JsonResponse
    {
        $salesOrder = $this->findSalesOrderForCurrentOrganization($salesOrderId);

        $this->authorize('pay', $salesOrder);

        $payment = $this->paymentService->recordSalesPayment(
            $salesOrder,
            $request->validated(),
            (int) $request->user()->id,
        );

        return $this->success(new PaymentResource($payment), status: 201);
    }

    public function deliver(int $salesOrderId): JsonResponse
    {
        $salesOrder = $this->findSalesOrderForCurrentOrganization($salesOrderId);

        $this->authorize('deliver', $salesOrder);

        $salesOrder = $this->salesOrderService->deliver($salesOrder);

        return $this->success(new SalesOrderResource($salesOrder));
    }

    public function refund(RecordRefundRequest $request, int $salesOrderId): JsonResponse
    {
        $salesOrder = $this->findSalesOrderForCurrentOrganization($salesOrderId);

        $this->authorize('refund', $salesOrder);

        $payment = $this->paymentService->recordSalesRefund(
            $salesOrder,
            $request->validated(),
            (int) $request->user()->id,
        );

        return $this->success(new PaymentResource($payment), status: 201);
    }

    protected function findSalesOrderForCurrentOrganization(int $salesOrderId): SalesOrder
    {
        return SalesOrder::query()
            ->whereKey($salesOrderId)
            ->where('organization_id', app('currentOrganization')->id)
            ->firstOrFail();
    }
}
