<?php

namespace App\Services;

use App\Exceptions\PlanLimitExceededException;
use App\Models\Organization;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;

class PlanLimitService
{
    public function __construct(
        protected OrganizationSubscriptionService $subscriptionService,
    ) {}

    public function assertCanCreateWarehouse(Organization $organization): void
    {
        $this->assertWithinLimit(
            $organization,
            'max_warehouses',
            Warehouse::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->count(),
            'warehouse',
        );
    }

    public function assertCanCreateProduct(Organization $organization): void
    {
        $this->assertWithinLimit(
            $organization,
            'max_products',
            Product::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->count(),
            'product',
        );
    }

    public function assertCanInviteUser(Organization $organization): void
    {
        $this->assertWithinLimit(
            $organization,
            'max_users',
            $organization->users()->count(),
            'team member',
        );
    }

    public function assertCanCreateOrder(Organization $organization): void
    {
        $startOfMonth = now()->startOfMonth();
        $count = SalesOrder::query()->withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('created_at', '>=', $startOfMonth)
            ->count()
            + PurchaseOrder::query()->withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where('created_at', '>=', $startOfMonth)
                ->count();

        $this->assertWithinLimit($organization, 'max_orders_per_month', $count, 'order');
    }

    protected function assertWithinLimit(
        Organization $organization,
        string $limitKey,
        int $currentCount,
        string $resourceLabel,
    ): void {
        $limits = $this->subscriptionService->activeLimits($organization);
        $max = $limits[$limitKey] ?? null;

        if ($max === null) {
            return;
        }

        if ($currentCount >= (int) $max) {
            throw new PlanLimitExceededException(
                "Plan limit reached: this organization cannot create more {$resourceLabel}s (limit {$max}).",
            );
        }
    }
}
