<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProductController extends ApiController
{
    public function __construct(
        protected ProductService $productService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $products = $this->productService->paginate();

        return $this->success(
            ProductResource::collection($products->items()),
            [
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                ],
            ],
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = $this->productService->create($request->validated());

        return $this->success(new ProductResource($product), status: 201);
    }

    public function show(int $productId): JsonResponse
    {
        $product = $this->findProductForCurrentOrganization($productId);

        $this->authorize('view', $product);

        return $this->success(new ProductResource($product));
    }

    public function update(UpdateProductRequest $request, int $productId): JsonResponse
    {
        $product = $this->findProductForCurrentOrganization($productId);

        $this->authorize('update', $product);

        $product = $this->productService->update($product, $request->validated());

        return $this->success(new ProductResource($product));
    }

    public function destroy(int $productId): Response
    {
        $product = $this->findProductForCurrentOrganization($productId);

        $this->authorize('delete', $product);

        $this->productService->delete($product);

        return response()->noContent();
    }

    protected function findProductForCurrentOrganization(int $productId): Product
    {
        return Product::query()
            ->whereKey($productId)
            ->where('organization_id', app('currentOrganization')->id)
            ->firstOrFail();
    }
}
