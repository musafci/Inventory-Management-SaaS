<?php

namespace App\Services;

use App\Models\Customer;
use App\Support\ListSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CustomerService
{
    /**
     * @return LengthAwarePaginator<int, Customer>
     */
    public function paginate(): LengthAwarePaginator
    {
        $query = Customer::query();
        ListSearch::applyToColumns($query, ['name', 'email', 'phone', 'address']);

        return QueryBuilder::for($query)
            ->allowedFilters(AllowedFilter::partial('name'), AllowedFilter::partial('email'))
            ->allowedSorts('name')
            ->defaultSort('name')
            ->paginate(request()->integer('per_page', 15));
    }

    public function create(array $data): Customer
    {
        return Customer::query()->create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->fill($data);
        $customer->save();

        return $customer->fresh();
    }

    public function delete(Customer $customer): void
    {
        if ($customer->salesOrders()->exists()) {
            throw ValidationException::withMessages([
                'customer' => ['Cannot delete a customer that has sales orders.'],
            ]);
        }

        $customer->delete();
    }
}
