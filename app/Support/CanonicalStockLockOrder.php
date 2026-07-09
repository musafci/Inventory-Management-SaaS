<?php

namespace App\Support;

/**
 * Ensures stock rows are locked in a consistent order across concurrent
 * multi-line operations (goods receipts, sales fulfillment, transfers).
 */
final class CanonicalStockLockOrder
{
    /**
     * @template T
     *
     * @param  list<T>  $lines
     * @param  callable(T): int  $productIdResolver
     * @return list<T>
     */
    public static function sortLinesByProductId(array $lines, callable $productIdResolver): array
    {
        usort(
            $lines,
            fn (mixed $left, mixed $right): int => $productIdResolver($left) <=> $productIdResolver($right),
        );

        return $lines;
    }
}
