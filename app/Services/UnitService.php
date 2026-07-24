<?php

namespace App\Services;

use App\Models\Unit;
use App\Support\ListSearch;
use App\Support\SyncFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UnitService
{
    /**
     * @return LengthAwarePaginator<int, Unit>
     */
    public function paginate(): LengthAwarePaginator
    {
        $query = Unit::query();
        ListSearch::applyToColumns($query, ['name', 'symbol']);

        return QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                SyncFilters::updatedAfter(),
            )
            ->allowedSorts('name')
            ->defaultSort('name')
            ->paginate(request()->integer('per_page', 15));
    }

    public function create(array $data): Unit
    {
        return Unit::query()->create([
            'name' => $data['name'],
            'symbol' => $data['symbol'],
        ]);
    }

    public function update(Unit $unit, array $data): Unit
    {
        $unit->fill($data);
        $unit->save();

        return $unit->fresh();
    }

    public function delete(Unit $unit): void
    {
        $unit->delete();
    }
}
