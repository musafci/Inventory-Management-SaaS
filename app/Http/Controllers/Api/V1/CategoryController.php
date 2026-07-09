<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CategoryController extends ApiController
{
    public function __construct(
        protected CategoryService $categoryService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $categories = $this->categoryService->paginate();

        return $this->success(
            CategoryResource::collection($categories->items()),
            [
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                    'last_page' => $categories->lastPage(),
                ],
            ],
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $category = $this->categoryService->create($request->validated());

        return $this->success(new CategoryResource($category), status: 201);
    }

    public function show(int $categoryId): JsonResponse
    {
        $category = $this->findCategoryForCurrentOrganization($categoryId);

        $this->authorize('view', $category);

        return $this->success(new CategoryResource($category));
    }

    public function update(UpdateCategoryRequest $request, int $categoryId): JsonResponse
    {
        $category = $this->findCategoryForCurrentOrganization($categoryId);

        $this->authorize('update', $category);

        $category = $this->categoryService->update($category, $request->validated());

        return $this->success(new CategoryResource($category));
    }

    public function destroy(int $categoryId): Response
    {
        $category = $this->findCategoryForCurrentOrganization($categoryId);

        $this->authorize('delete', $category);

        $this->categoryService->delete($category);

        return response()->noContent();
    }

    protected function findCategoryForCurrentOrganization(int $categoryId): Category
    {
        return Category::query()
            ->whereKey($categoryId)
            ->where('organization_id', app('currentOrganization')->id)
            ->firstOrFail();
    }
}
