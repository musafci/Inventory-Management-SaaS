import {
  canCreateInventory,
  canViewDashboard,
  canViewPurchasing,
  canViewReports,
  canViewSales,
} from '@/src/permissions';

const ownerPermissions = [
  'inventory.view',
  'inventory.create',
  'reports.view_inventory',
  'reports.view_sales',
  'reports.view_purchases',
  'customers.view',
  'suppliers.view',
  'orders.purchase.view',
];

describe('permissions', () => {
  it('grants dashboard when any report or inventory permission exists', () => {
    expect(canViewDashboard(ownerPermissions)).toBe(true);
    expect(canViewDashboard(['inventory.view'])).toBe(true);
    expect(canViewDashboard(['customers.view'])).toBe(false);
  });

  it('gates module tabs correctly', () => {
    expect(canCreateInventory(ownerPermissions)).toBe(true);
    expect(canViewSales(ownerPermissions)).toBe(true);
    expect(canViewPurchasing(ownerPermissions)).toBe(true);
    expect(canViewReports(ownerPermissions)).toBe(true);
  });
});
