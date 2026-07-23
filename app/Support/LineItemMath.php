<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class LineItemMath
{
    public static function subtotal(int $quantity, float $unitAmount, float $discount = 0): string
    {
        $subtotal = max(0, round(($quantity * $unitAmount) - $discount, 2));

        return number_format($subtotal, 2, '.', '');
    }

    public static function assertDiscountWithinLineTotal(
        float $discount,
        int $quantity,
        float $unitAmount,
        string $field,
    ): void {
        if ($discount < 0) {
            throw ValidationException::withMessages([
                $field => ['Discount cannot be negative.'],
            ]);
        }

        $lineTotal = round($quantity * $unitAmount, 2);

        if ($discount > $lineTotal) {
            throw ValidationException::withMessages([
                $field => ['Discount cannot exceed the line total.'],
            ]);
        }
    }
}
