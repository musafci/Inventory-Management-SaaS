<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payable_type' => SalesOrder::class,
            'payable_id' => SalesOrder::factory(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'method' => PaymentMethod::Cash,
            'status' => PaymentStatus::Completed,
            'reference' => fake()->optional()->uuid(),
            'note' => fake()->optional()->sentence(),
            'recorded_by' => User::factory(),
            'paid_at' => now(),
        ];
    }
}
