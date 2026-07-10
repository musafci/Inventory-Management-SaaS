<?php

namespace App\Events;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockLevelChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Stock $stock,
        public Product $product,
        public int $previousQuantityOnHand,
        public int $newQuantityOnHand,
    ) {}
}
