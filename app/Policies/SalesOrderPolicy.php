<?php

namespace App\Policies;

use App\Models\SalesOrder;
use App\Models\User;

class SalesOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.sales.view');
    }

    public function view(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('orders.sales.view');
    }

    public function create(User $user): bool
    {
        return $user->can('orders.sales.create');
    }

    public function update(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('orders.sales.update');
    }

    public function delete(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('orders.sales.delete');
    }

    public function confirm(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('orders.sales.confirm');
    }

    public function cancel(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('orders.sales.update');
    }

    public function fulfill(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('orders.sales.fulfill');
    }

    public function pay(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('orders.sales.pay');
    }

    public function deliver(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('orders.sales.deliver');
    }

    public function refund(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('orders.sales.refund');
    }
}
