<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Database\Factories\SalesFulfillmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'organization_id',
    'sales_order_id',
    'fulfilled_by',
    'note',
    'fulfilled_at',
])]
class SalesFulfillment extends Model
{
    /** @use HasFactory<SalesFulfillmentFactory> */
    use BelongsToOrganization, HasFactory;

    protected function casts(): array
    {
        return [
            'fulfilled_at' => 'datetime',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function fulfilledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesFulfillmentItem::class);
    }
}
