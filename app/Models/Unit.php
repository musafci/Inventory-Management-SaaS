<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['organization_id', 'name', 'symbol'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use BelongsToOrganization, HasFactory;
}
