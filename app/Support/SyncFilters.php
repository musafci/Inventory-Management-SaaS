<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;

class SyncFilters
{
    public static function updatedAfter(): AllowedFilter
    {
        return AllowedFilter::callback('updated_after', function (Builder $query, mixed $value): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            $query->where('updated_at', '>=', Carbon::parse($value));
        });
    }
}
