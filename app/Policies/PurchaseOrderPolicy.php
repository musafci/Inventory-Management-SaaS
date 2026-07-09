<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchase_orders.view');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.view');
    }

    public function create(User $user): bool
    {
        return $user->can('purchase_orders.create');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.update');
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.delete');
    }

    public function send(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.update');
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.update');
    }

    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.receive');
    }

    public function pay(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.pay');
    }
}
