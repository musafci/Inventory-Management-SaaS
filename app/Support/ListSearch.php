<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ListSearch
{
    public static function term(?Request $request = null): ?string
    {
        $search = ($request ?? request())->query('search');

        if (! is_string($search)) {
            return null;
        }

        $search = trim($search);

        return $search === '' ? null : $search;
    }

    public static function likeTerm(?Request $request = null): ?string
    {
        $term = self::term($request);

        return $term !== null ? '%'.$term.'%' : null;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $columns
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public static function applyToColumns(Builder $query, array $columns, ?Request $request = null): Builder
    {
        $like = self::likeTerm($request);

        if ($like === null || $columns === []) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($columns, $like): void {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $builder->{$method}($column, 'like', $like);
            }
        });
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  list<array{relation?: string|null, columns: list<string>}>  $scopes
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public static function apply(Builder $query, array $scopes, ?Request $request = null): Builder
    {
        $like = self::likeTerm($request);

        if ($like === null || $scopes === []) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($scopes, $like): void {
            foreach ($scopes as $index => $scope) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $columns = $scope['columns'];
                $relation = $scope['relation'] ?? null;

                if ($relation === null) {
                    $builder->{$method}(function (Builder $columnQuery) use ($columns, $like): void {
                        foreach ($columns as $columnIndex => $column) {
                            $columnMethod = $columnIndex === 0 ? 'where' : 'orWhere';
                            $columnQuery->{$columnMethod}($column, 'like', $like);
                        }
                    });

                    continue;
                }

                $builder->{$method}(function (Builder $relationWrapper) use ($relation, $columns, $like): void {
                    $relationWrapper->whereHas($relation, function (Builder $relationQuery) use ($columns, $like): void {
                        $relationQuery->where(function (Builder $columnQuery) use ($columns, $like): void {
                            foreach ($columns as $columnIndex => $column) {
                                $columnMethod = $columnIndex === 0 ? 'where' : 'orWhere';
                                $columnQuery->{$columnMethod}($column, 'like', $like);
                            }
                        });
                    });
                });
            }
        });
    }
}
