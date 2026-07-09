<?php

namespace App\Observers;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Validation\ValidationException;

class StockMovementObserver
{
    /**
     * CRITICAL: This observer is the ONLY code path allowed to mutate stocks.quantity_on_hand.
     * It runs inside the DB::transaction opened by StockService::recordMovement(),
     * after the stock row has already been locked via lockForUpdate().
     */
    public function created(StockMovement $movement): void
    {
        $stock = Stock::query()
            ->where('warehouse_id', $movement->warehouse_id)
            ->where('product_id', $movement->product_id)
            ->lockForUpdate()
            ->firstOrFail();

        $nextQuantity = $stock->quantity_on_hand + $movement->signedQuantityDelta();

        if ($nextQuantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => ['Insufficient stock for this movement.'],
            ]);
        }

        Stock::$quantityOnHandUpdateFromMovement = true;

        try {
            $stock->quantity_on_hand = $nextQuantity;
            $stock->save();
        } finally {
            Stock::$quantityOnHandUpdateFromMovement = false;
        }
    }
}
