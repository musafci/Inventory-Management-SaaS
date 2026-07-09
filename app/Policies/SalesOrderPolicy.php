<?php

namespace App\Policies;

use App\Models\SalesOrder;
use App\Models\User;

class SalesOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales_orders.view');
    }

    public function view(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sales_orders.create');
    }

    public function update(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.update');
    }

    public function delete(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.delete');
    }

    public function confirm(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.confirm');
    }

    public function cancel(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.update');
    }

    public function fulfill(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.fulfill');
    }

    public function pay(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.pay');
    }

    public function deliver(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.deliver');
    }

    public function refund(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.pay');
    }
}
