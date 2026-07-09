<?php

namespace App\Models;

use App\Enums\SalesOrderStatus;
use App\Traits\BelongsToOrganization;
use App\Traits\LogsModelActivity;
use Database\Factories\SalesOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'organization_id',
    'customer_id',
    'warehouse_id',
    'order_number',
    'status',
    'order_date',
    'total_amount',
])]
class SalesOrder extends Model
{
    /** @use HasFactory<SalesOrderFactory> */
    use BelongsToOrganization, HasFactory, LogsModelActivity;

    protected function casts(): array
    {
        return [
            'status' => SalesOrderStatus::class,
            'order_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
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
