<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SupplierService
{
    /**
     * @return LengthAwarePaginator<int, Supplier>
     */
    public function paginate(): LengthAwarePaginator
    {
        return QueryBuilder::for(Supplier::class)
            ->allowedFilters(AllowedFilter::partial('name'))
            ->allowedSorts('name')
            ->defaultSort('name')
            ->paginate(request()->integer('per_page', 15));
    }

    public function create(array $data): Supplier
    {
        return Supplier::query()->create([
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->fill($data);
        $supplier->save();

        return $supplier->fresh();
    }

    public function delete(Supplier $supplier): void
    {
        if ($supplier->purchaseOrders()->exists()) {
            throw ValidationException::withMessages([
                'supplier' => ['Cannot delete a supplier that has purchase orders.'],
            ]);
        }

        $supplier->delete();
    }
}
