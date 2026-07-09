<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'category_id',
    'unit_id',
    'name',
    'sku',
    'barcode',
    'cost_price',
    'selling_price',
    'tax_rate',
    'reorder_point',
    'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToOrganization, HasFactory;

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'reorder_point' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
