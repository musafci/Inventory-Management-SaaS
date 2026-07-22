<?php

namespace App\Services;

use App\Models\Product;
use App\Services\PlanLimitService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductService
{
    public function __construct(
        protected PlanLimitService $planLimitService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(): LengthAwarePaginator
    {
        $query = Product::query();

        $search = request()->query('search');

        if (is_string($search) && $search !== '') {
            $term = '%'.$search.'%';

            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('barcode', 'like', $term);
            });
        }

        return QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::exact('category_id'),
                AllowedFilter::partial('sku'),
            )
            ->allowedSorts('name')
            ->defaultSort('name')
            ->paginate(request()->integer('per_page', 15));
    }

    public function create(array $data): Product
    {
        $this->planLimitService->assertCanCreateProduct(app('currentOrganization'));

        return Product::query()->create([
            'category_id' => $data['category_id'],
            'unit_id' => $data['unit_id'],
            'name' => $data['name'],
            'sku' => $data['sku'],
            'barcode' => $data['barcode'] ?? null,
            'cost_price' => $data['cost_price'],
            'selling_price' => $data['selling_price'],
            'tax_rate' => $data['tax_rate'] ?? 0,
            'reorder_point' => $data['reorder_point'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(Product $product, array $data): Product
    {
        $product->fill($data);
        $product->save();

        return $product->fresh();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
