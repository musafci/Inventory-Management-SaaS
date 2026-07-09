<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'organization_id',
    'name',
    'email',
    'phone',
    'address',
])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use BelongsToOrganization, HasFactory;

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }
}
