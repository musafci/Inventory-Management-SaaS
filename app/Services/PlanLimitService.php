<?php

namespace App\Services;

use App\Exceptions\PlanLimitExceededException;
use App\Models\Organization;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\PlanWarningCollector;

class PlanLimitService
{
    public function __construct(
        protected OrganizationSubscriptionService $subscriptionService,
        protected PlanWarningCollector $warningCollector,
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

    public function evaluateOrganizationWarnings(Organization $organization): ?string
    {
        $this->warningCollector->clear();

        $this->recordWarning(
            $organization,
            'max_warehouses',
            Warehouse::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->count(),
        );
        $this->recordWarning(
            $organization,
            'max_products',
            Product::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->count(),
        );
        $this->recordWarning(
            $organization,
            'max_users',
            $organization->users()->count(),
        );

        $startOfMonth = now()->startOfMonth();
        $orderCount = SalesOrder::query()->withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('created_at', '>=', $startOfMonth)
            ->count()
            + PurchaseOrder::query()->withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where('created_at', '>=', $startOfMonth)
                ->count();

        $this->recordWarning($organization, 'max_orders_per_month', $orderCount);

        return $this->warningCollector->current();
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

        $max = (int) $max;
        $gracePercent = $this->subscriptionService->graceBufferPercent($organization);
        $hardCap = $this->hardCap($max, $gracePercent);

        $this->recordWarning($organization, $limitKey, $currentCount);

        if ($currentCount >= $hardCap) {
            throw new PlanLimitExceededException(
                "Upgrade required: this organization has reached the {$resourceLabel} limit for its plan (limit {$max}).",
            );
        }
    }

    protected function recordWarning(Organization $organization, string $limitKey, int $currentCount): void
    {
        $limits = $this->subscriptionService->activeLimits($organization);
        $max = $limits[$limitKey] ?? null;

        if ($max === null) {
            return;
        }

        $max = (int) $max;
        $gracePercent = $this->subscriptionService->graceBufferPercent($organization);
        $hardCap = $this->hardCap($max, $gracePercent);

        if ($currentCount >= $hardCap) {
            return;
        }

        if ($currentCount >= $max) {
            $this->warningCollector->record('over_limit_grace');

            return;
        }

        if ($currentCount >= (int) floor($max * 0.9)) {
            $this->warningCollector->record('approaching_limit');
        }
    }

    protected function hardCap(int $max, int $gracePercent): int
    {
        return $max + (int) ceil($max * $gracePercent / 100);
    }
}
