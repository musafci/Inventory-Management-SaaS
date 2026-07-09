<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends ApiController
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $payments = $this->paymentService->paginate();

        return $this->success(
            PaymentResource::collection($payments->items()),
            [
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'last_page' => $payments->lastPage(),
                ],
            ],
        );
    }

    public function show(int $paymentId): JsonResponse
    {
        $payment = Payment::query()
            ->with(['payable', 'recordedBy'])
            ->whereKey($paymentId)
            ->firstOrFail();

        $this->authorize('view', $payment);

        return $this->success(new PaymentResource($payment));
    }
}
