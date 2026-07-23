<?php

namespace App\Services;

use App\Models\Category;
use App\Support\ListSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CategoryService
{
    /**
     * @return LengthAwarePaginator<int, Category>
     */
    public function paginate(): LengthAwarePaginator
    {
        $query = Category::query();
        ListSearch::applyToColumns($query, ['name', 'slug']);

        return QueryBuilder::for($query)
            ->allowedFilters(AllowedFilter::partial('name'))
            ->allowedSorts('name')
            ->defaultSort('name')
            ->paginate(request()->integer('per_page', 15));
    }

    public function create(array $data): Category
    {
        return Category::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $this->uniqueSlug($data['name']),
            'parent_id' => $data['parent_id'] ?? null,
        ]);
    }

    public function update(Category $category, array $data): Category
    {
        if (array_key_exists('parent_id', $data) && $data['parent_id'] !== null) {
            $this->assertNoParentCycle($category, (int) $data['parent_id']);
        }

        if (array_key_exists('name', $data) && ! array_key_exists('slug', $data)) {
            $data['slug'] = $this->uniqueSlug($data['name'], $category->id);
        }

        $category->fill($data);
        $category->save();

        return $category->fresh();
    }

    public function delete(Category $category): void
    {
        if ($category->children()->exists()) {
            throw ValidationException::withMessages([
                'category' => ['Cannot delete a category that has child categories.'],
            ]);
        }

        $category->delete();
    }

    protected function assertNoParentCycle(Category $category, int $newParentId): void
    {
        if ($category->id === $newParentId) {
            throw ValidationException::withMessages([
                'parent_id' => ['A category cannot be its own parent.'],
            ]);
        }

        $cursor = Category::query()->find($newParentId);

        while ($cursor !== null) {
            if ($cursor->id === $category->id) {
                throw ValidationException::withMessages([
                    'parent_id' => ['A category cannot be nested under one of its descendants.'],
                ]);
            }

            $cursor = $cursor->parent_id !== null
                ? Category::query()->find($cursor->parent_id)
                : null;
        }
    }

    protected function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base === '' ? 'category' : $base;
        $counter = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    protected function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = Category::query()->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
