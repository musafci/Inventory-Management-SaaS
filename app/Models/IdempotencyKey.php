<?php

namespace App\Models;

use App\Enums\IdempotencyKeyStatus;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'user_id',
    'idempotency_key',
    'route_fingerprint',
    'request_hash',
    'status',
    'response_status_code',
    'response_body',
    'completed_at',
])]
class IdempotencyKey extends Model
{
    use BelongsToOrganization;

    protected function casts(): array
    {
        return [
            'status' => IdempotencyKeyStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
