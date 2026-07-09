<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $symbol = fake()->unique()->lexify('???');

        return [
            'organization_id' => Organization::factory(),
            'name' => strtoupper($symbol),
            'symbol' => $symbol,
        ];
    }
}
