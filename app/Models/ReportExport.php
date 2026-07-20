<?php

namespace App\Models;

use App\Enums\ReportExportStatus;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'user_id',
    'type',
    'status',
    'file_path',
    'error_message',
    'completed_at',
])]
class ReportExport extends Model
{
    use BelongsToOrganization;

    protected function casts(): array
    {
        return [
            'status' => ReportExportStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
