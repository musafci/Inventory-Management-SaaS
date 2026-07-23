export function can(permissions: string[], permission: string): boolean {
  return permissions.includes(permission);
}

export function canAny(permissions: string[], keys: string[]): boolean {
  return keys.some((key) => permissions.includes(key));
}

export function canAccessSettings(permissions: string[]): boolean {
  return canAny(permissions, [
    'settings.view',
    'settings.update',
    'settings.manage_users',
    'settings.manage_roles',
  ]);
}

export function canViewDashboard(permissions: string[]): boolean {
  return canAny(permissions, [
    'reports.view_inventory',
    'reports.view_sales',
    'reports.view_purchases',
    'inventory.view',
  ]);
}

export function canViewInventory(permissions: string[]): boolean {
  return can(permissions, 'inventory.view');
}

export function canCreateInventory(permissions: string[]): boolean {
  return can(permissions, 'inventory.create');
}

export function canUpdateInventory(permissions: string[]): boolean {
  return can(permissions, 'inventory.update');
}

export function canDeleteInventory(permissions: string[]): boolean {
  return can(permissions, 'inventory.delete');
}

export function canViewSales(permissions: string[]): boolean {
  return canAny(permissions, ['customers.view', 'orders.sales.view', 'payments.view']);
}

export function canViewPurchasing(permissions: string[]): boolean {
  return canAny(permissions, ['suppliers.view', 'orders.purchase.view']);
}

export function canViewReports(permissions: string[]): boolean {
  return canAny(permissions, [
    'reports.view_sales',
    'reports.view_inventory',
    'reports.view_purchases',
  ]);
}
