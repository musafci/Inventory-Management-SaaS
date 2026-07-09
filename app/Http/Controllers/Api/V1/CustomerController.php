<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CustomerController extends ApiController
{
    public function __construct(
        protected CustomerService $customerService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $customers = $this->customerService->paginate();

        return $this->success(
            CustomerResource::collection($customers->items()),
            [
                'pagination' => [
                    'current_page' => $customers->currentPage(),
                    'per_page' => $customers->perPage(),
                    'total' => $customers->total(),
                    'last_page' => $customers->lastPage(),
                ],
            ],
        );
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $customer = $this->customerService->create($request->validated());

        return $this->success(new CustomerResource($customer), status: 201);
    }

    public function show(int $customerId): JsonResponse
    {
        $customer = $this->findCustomerForCurrentOrganization($customerId);

        $this->authorize('view', $customer);

        return $this->success(new CustomerResource($customer));
    }

    public function update(UpdateCustomerRequest $request, int $customerId): JsonResponse
    {
        $customer = $this->findCustomerForCurrentOrganization($customerId);

        $this->authorize('update', $customer);

        $customer = $this->customerService->update($customer, $request->validated());

        return $this->success(new CustomerResource($customer));
    }

    public function destroy(int $customerId): Response
    {
        $customer = $this->findCustomerForCurrentOrganization($customerId);

        $this->authorize('delete', $customer);

        $this->customerService->delete($customer);

        return response()->noContent();
    }

    protected function findCustomerForCurrentOrganization(int $customerId): Customer
    {
        return Customer::query()
            ->whereKey($customerId)
            ->where('organization_id', app('currentOrganization')->id)
            ->firstOrFail();
    }
}
