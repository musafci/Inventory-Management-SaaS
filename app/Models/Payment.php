<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Traits\BelongsToOrganization;
use App\Traits\LogsModelActivity;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'organization_id',
    'payable_type',
    'payable_id',
    'amount',
    'method',
    'status',
    'reference',
    'note',
    'recorded_by',
    'paid_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use BelongsToOrganization, HasFactory, LogsModelActivity;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
