<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Database\Factories\WarehouseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['organization_id', 'name', 'address', 'is_default'])]
class Warehouse extends Model
{
    /** @use HasFactory<WarehouseFactory> */
    use BelongsToOrganization, HasFactory;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
