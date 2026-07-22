<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.purchase.view');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('orders.purchase.view');
    }

    public function create(User $user): bool
    {
        return $user->can('orders.purchase.create');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('orders.purchase.update');
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('orders.purchase.delete');
    }

    public function send(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('orders.purchase.send');
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('orders.purchase.update');
    }

    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('orders.purchase.receive');
    }

    public function pay(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('orders.purchase.pay');
    }
}
