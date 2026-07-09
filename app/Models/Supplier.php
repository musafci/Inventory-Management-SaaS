<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'organization_id',
    'name',
    'contact_person',
    'email',
    'phone',
    'address',
])]
class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use BelongsToOrganization, HasFactory;

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
