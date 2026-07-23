<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Services\PlanLimitService;
use App\Support\ListSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class WarehouseService
{
    public function __construct(
        protected PlanLimitService $planLimitService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Warehouse>
     */
    public function paginate(): LengthAwarePaginator
    {
        $query = Warehouse::query();
        ListSearch::applyToColumns($query, ['name', 'address']);

        return QueryBuilder::for($query)
            ->allowedFilters(AllowedFilter::partial('name'))
            ->allowedSorts('name')
            ->defaultSort('name')
            ->paginate(request()->integer('per_page', 15));
    }

    public function create(array $data): Warehouse
    {
        $organization = app('currentOrganization');
        $this->planLimitService->assertCanCreateWarehouse($organization);

        return DB::transaction(function () use ($data) {
            $shouldBeDefault = ($data['is_default'] ?? false) || ! $this->organizationHasWarehouses();

            $warehouse = Warehouse::query()->create([
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
                'is_default' => $shouldBeDefault,
            ]);

            if ($warehouse->is_default) {
                $this->clearOtherDefaults($warehouse);
            }

            return $warehouse->fresh();
        });
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        return DB::transaction(function () use ($warehouse, $data) {
            $warehouse->fill($data);
            $warehouse->save();

            if (array_key_exists('is_default', $data) && $data['is_default']) {
                $this->clearOtherDefaults($warehouse);
            }

            if (! $this->organizationHasDefaultWarehouse()) {
                $warehouse->forceFill(['is_default' => true])->save();
            }

            return $warehouse->fresh();
        });
    }

    public function delete(Warehouse $warehouse): void
    {
        DB::transaction(function () use ($warehouse) {
            $wasDefault = $warehouse->is_default;

            $warehouse->delete();

            if ($wasDefault) {
                $replacement = Warehouse::query()->oldest('id')->first();

                if ($replacement !== null) {
                    $replacement->forceFill(['is_default' => true])->save();
                    $this->clearOtherDefaults($replacement);
                }
            }
        });
    }

    protected function organizationHasWarehouses(): bool
    {
        return Warehouse::query()->exists();
    }

    protected function organizationHasDefaultWarehouse(): bool
    {
        return Warehouse::query()->where('is_default', true)->exists();
    }

    protected function clearOtherDefaults(Warehouse $warehouse): void
    {
        Warehouse::query()
            ->whereKeyNot($warehouse->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}
