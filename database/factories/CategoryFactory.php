<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'organization_id' => Organization::factory(),
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }

    public function childOf(Category $parent): static
    {
        return $this->state([
            'organization_id' => $parent->organization_id,
            'parent_id' => $parent->id,
        ]);
    }
}
