<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Import\ImportCsvRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\ProductImportService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

#[Group('Products', description: 'Product catalog CRUD within the active organization.', weight: 10)]
class ProductController extends ApiController
{
    public function __construct(
        protected ProductService $productService,
        protected ProductImportService $productImportService,
    ) {}

    #[Endpoint(operationId: 'products.index', title: 'List products', description: 'Returns a paginated list of products for the active organization.')]
    #[ApiResponse(
        status: 200,
        description: 'Paginated product list.',
        examples: [[
            'data' => [[
                'id' => 1,
                'organization_id' => 1,
                'category_id' => 1,
                'unit_id' => 1,
                'name' => 'Tracked Item',
                'sku' => 'STK-1234',
                'barcode' => null,
                'cost_price' => '5.00',
                'selling_price' => '10.00',
                'tax_rate' => '0.00',
                'reorder_point' => null,
                'is_active' => true,
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

    #[Endpoint(operationId: 'products.store', title: 'Create product', description: 'Creates a product scoped to the active organization.')]
    #[ApiResponse(
        status: 201,
        description: 'Product created.',
        examples: [[
            'data' => [
                'id' => 1,
                'organization_id' => 1,
                'category_id' => 1,
                'unit_id' => 1,
                'name' => 'Tracked Item',
                'sku' => 'STK-1234',
                'barcode' => null,
                'cost_price' => '5.00',
                'selling_price' => '10.00',
                'tax_rate' => '0.00',
                'reorder_point' => null,
                'is_active' => true,
                'created_at' => '2026-07-10T12:00:00.000000Z',
                'updated_at' => '2026-07-10T12:00:00.000000Z',
            ],
        ]],
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = $this->productService->create($request->validated());

        return $this->success(new ProductResource($product), status: 201);
    }

    public function import(ImportCsvRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        try {
            $result = $this->productImportService->import(
                app('currentOrganization'),
                $request->validated('csv'),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'csv' => [$exception->getMessage()],
            ]);
        }

        return $this->success($result);
    }

    #[Endpoint(operationId: 'products.show', title: 'Show product', description: 'Returns a single product by ID within the active organization.')]
    #[ApiResponse(
        status: 200,
        description: 'Product details.',
        examples: [[
            'data' => [
                'id' => 1,
                'organization_id' => 1,
                'category_id' => 1,
                'unit_id' => 1,
                'name' => 'Tracked Item',
                'sku' => 'STK-1234',
                'barcode' => null,
                'cost_price' => '5.00',
                'selling_price' => '10.00',
                'tax_rate' => '0.00',
                'reorder_point' => 10,
                'is_active' => true,
                'created_at' => '2026-07-10T12:00:00.000000Z',
                'updated_at' => '2026-07-10T12:00:00.000000Z',
            ],
        ]],
    )]
    public function show(int $productId): JsonResponse
    {
        $product = $this->findProductForCurrentOrganization($productId);

        $this->authorize('view', $product);

        return $this->success(new ProductResource($product));
    }

    #[Endpoint(operationId: 'products.update', title: 'Update product', description: 'Updates a product within the active organization.')]
    #[ApiResponse(
        status: 200,
        description: 'Product updated.',
        examples: [[
            'data' => [
                'id' => 1,
                'organization_id' => 1,
                'category_id' => 1,
                'unit_id' => 1,
                'name' => 'Tracked Item (Revised)',
                'sku' => 'STK-1234',
                'barcode' => '0123456789012',
                'cost_price' => '6.00',
                'selling_price' => '12.00',
                'tax_rate' => '5.00',
                'reorder_point' => 10,
                'is_active' => true,
                'created_at' => '2026-07-10T12:00:00.000000Z',
                'updated_at' => '2026-07-10T13:00:00.000000Z',
            ],
        ]],
    )]
    public function update(UpdateProductRequest $request, int $productId): JsonResponse
    {
        $product = $this->findProductForCurrentOrganization($productId);

        $this->authorize('update', $product);

        $product = $this->productService->update($product, $request->validated());

        return $this->success(new ProductResource($product));
    }

    #[Endpoint(operationId: 'products.destroy', title: 'Delete product', description: 'Soft-deletes a product within the active organization.')]
    #[ApiResponse(status: 204, description: 'Product deleted.')]
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
