<?php

namespace App\Permission;

final class PermissionCatalog
{
    public const SYSTEM_OWNER_ROLE = 'System Owner';

    public const ORG_OWNER_ROLE = 'Org Owner';

    /**
     * @return array<string, list<string>>
     */
    public static function groups(): array
    {
        return [
            'Inventory' => [
                'inventory.view',
                'inventory.create',
                'inventory.update',
                'inventory.delete',
            ],
            'Orders' => [
                'orders.purchase.view',
                'orders.purchase.create',
                'orders.purchase.update',
                'orders.purchase.delete',
                'orders.purchase.send',
                'orders.purchase.receive',
                'orders.purchase.pay',
                'orders.sales.view',
                'orders.sales.create',
                'orders.sales.update',
                'orders.sales.delete',
                'orders.sales.confirm',
                'orders.sales.fulfill',
                'orders.sales.deliver',
                'orders.sales.pay',
                'orders.sales.refund',
            ],
            'Customers' => [
                'customers.view',
                'customers.create',
                'customers.update',
                'customers.delete',
            ],
            'Suppliers' => [
                'suppliers.view',
                'suppliers.create',
                'suppliers.update',
                'suppliers.delete',
            ],
            'Payments' => [
                'payments.view',
            ],
            'Reports' => [
                'reports.view_sales',
                'reports.view_inventory',
                'reports.view_purchases',
                'reports.export',
            ],
            'Settings' => [
                'settings.view',
                'settings.update',
                'settings.manage_users',
                'settings.manage_roles',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::groups()))));
    }

    /**
     * @return list<string>
     */
    public static function protectedRoleNames(): array
    {
        return [self::SYSTEM_OWNER_ROLE];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function defaultRolePermissions(): array
    {
        return [
            self::SYSTEM_OWNER_ROLE => self::all(),
            self::ORG_OWNER_ROLE => self::all(),
            'Admin' => self::all(),
            'Manager' => [
                'inventory.view',
                'inventory.create',
                'inventory.update',
                'orders.purchase.view',
                'orders.purchase.create',
                'orders.purchase.update',
                'orders.purchase.send',
                'orders.purchase.receive',
                'orders.sales.view',
                'orders.sales.create',
                'orders.sales.update',
                'orders.sales.confirm',
                'orders.sales.fulfill',
                'customers.view',
                'customers.create',
                'customers.update',
                'suppliers.view',
                'suppliers.create',
                'suppliers.update',
                'payments.view',
                'reports.view_sales',
                'reports.view_inventory',
                'reports.view_purchases',
                'settings.view',
            ],
            'Warehouse Staff' => [
                'inventory.view',
                'inventory.update',
                'orders.purchase.view',
                'orders.purchase.receive',
                'orders.sales.view',
                'orders.sales.fulfill',
                'orders.sales.deliver',
            ],
            'Sales Staff' => [
                'inventory.view',
                'orders.sales.view',
                'orders.sales.create',
                'orders.sales.update',
                'orders.sales.confirm',
                'orders.sales.pay',
                'customers.view',
                'customers.create',
                'customers.update',
                'payments.view',
            ],
            'Viewer' => [
                'inventory.view',
                'orders.purchase.view',
                'orders.sales.view',
                'customers.view',
                'suppliers.view',
                'payments.view',
                'reports.view_sales',
                'reports.view_inventory',
                'reports.view_purchases',
                'settings.view',
            ],
        ];
    }

    /**
     * @return array<string, array{description: string, is_protected: bool, is_system: bool}>
     */
    public static function defaultRoleMetadata(): array
    {
        return [
            self::SYSTEM_OWNER_ROLE => [
                'description' => 'Unrestricted system access. This role cannot be modified or deleted.',
                'is_protected' => true,
                'is_system' => true,
            ],
            self::ORG_OWNER_ROLE => [
                'description' => 'Shop owner with configurable permissions.',
                'is_protected' => false,
                'is_system' => true,
            ],
            'Admin' => [
                'description' => 'Full operational access with configurable permissions.',
                'is_protected' => false,
                'is_system' => true,
            ],
            'Manager' => [
                'description' => 'Manage inventory, purchasing, and sales day to day.',
                'is_protected' => false,
                'is_system' => true,
            ],
            'Warehouse Staff' => [
                'description' => 'Receive purchase orders and fulfill sales orders.',
                'is_protected' => false,
                'is_system' => true,
            ],
            'Sales Staff' => [
                'description' => 'Create and manage customer sales orders.',
                'is_protected' => false,
                'is_system' => true,
            ],
            'Viewer' => [
                'description' => 'Read-only access to operational data and reports.',
                'is_protected' => false,
                'is_system' => true,
            ],
        ];
    }
}
