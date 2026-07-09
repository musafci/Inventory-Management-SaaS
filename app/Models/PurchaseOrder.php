<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Traits\BelongsToOrganization;
use App\Traits\LogsModelActivity;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'organization_id',
    'supplier_id',
    'warehouse_id',
    'po_number',
    'status',
    'order_date',
    'expected_date',
    'total_amount',
])]
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use BelongsToOrganization, HasFactory, LogsModelActivity;

    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'order_date' => 'date',
            'expected_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function netAmountPaid(): string
    {
        $completed = (float) $this->payments()
            ->where('status', \App\Enums\PaymentStatus::Completed)
            ->sum('amount');

        $refunded = (float) $this->payments()
            ->where('status', \App\Enums\PaymentStatus::Refunded)
            ->sum('amount');

        return number_format(max(0, $completed - $refunded), 2, '.', '');
    }

    public function amountDue(): string
    {
        $due = (float) $this->total_amount - (float) $this->netAmountPaid();

        return number_format(max(0, $due), 2, '.', '');
    }
}
