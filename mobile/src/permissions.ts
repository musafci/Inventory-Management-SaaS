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

export function canCreateSupplier(permissions: string[]): boolean {
  return can(permissions, 'suppliers.create');
}

export function canUpdateSupplier(permissions: string[]): boolean {
  return can(permissions, 'suppliers.update');
}

export function canDeleteSupplier(permissions: string[]): boolean {
  return can(permissions, 'suppliers.delete');
}

export function canCreateCustomer(permissions: string[]): boolean {
  return can(permissions, 'customers.create');
}

export function canUpdateCustomer(permissions: string[]): boolean {
  return can(permissions, 'customers.update');
}

export function canDeleteCustomer(permissions: string[]): boolean {
  return can(permissions, 'customers.delete');
}

export function canCreatePurchaseOrder(permissions: string[]): boolean {
  return can(permissions, 'orders.purchase.create');
}

export function canUpdatePurchaseOrder(permissions: string[]): boolean {
  return can(permissions, 'orders.purchase.update');
}

export function canDeletePurchaseOrder(permissions: string[]): boolean {
  return can(permissions, 'orders.purchase.delete');
}

export function canCreateSalesOrder(permissions: string[]): boolean {
  return can(permissions, 'orders.sales.create');
}

export function canUpdateSalesOrder(permissions: string[]): boolean {
  return can(permissions, 'orders.sales.update');
}

export function canDeleteSalesOrder(permissions: string[]): boolean {
  return can(permissions, 'orders.sales.delete');
}

export function canCreatePayment(permissions: string[]): boolean {
  return canAny(permissions, ['orders.purchase.pay', 'orders.sales.pay']);
}

export function canUpdatePayment(permissions: string[]): boolean {
  return canCreatePayment(permissions);
}

export function canDeletePayment(permissions: string[]): boolean {
  return can(permissions, 'orders.sales.refund');
}

export function canViewInventoryReports(permissions: string[]): boolean {
  return can(permissions, 'reports.view_inventory');
}

export function canViewSalesReports(permissions: string[]): boolean {
  return can(permissions, 'reports.view_sales');
}

export function canViewPurchaseReports(permissions: string[]): boolean {
  return can(permissions, 'reports.view_purchases');
}

export function canExportReports(permissions: string[]): boolean {
  return can(permissions, 'reports.export');
}

export function canViewOrganization(permissions: string[]): boolean {
  return can(permissions, 'settings.view');
}

export function canUpdateOrganization(permissions: string[]): boolean {
  return can(permissions, 'settings.update');
}

export function canManageUsers(permissions: string[]): boolean {
  return can(permissions, 'settings.manage_users');
}

export function canManageRoles(permissions: string[]): boolean {
  return can(permissions, 'settings.manage_roles');
}
