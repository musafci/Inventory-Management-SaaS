import { fetchAllPayments } from '@/src/api/payments';
import { fetchAllPurchaseOrders, fetchAllSalesOrders } from '@/src/api/orders';
import { upsertPurchaseOrders, upsertSalesOrders } from '@/src/db/ordersCache';
import { upsertPayments } from '@/src/db/paymentsCache';
import { getSyncCursor, setSyncCursor } from '@/src/db/syncMetadata';

export async function pullOrdersCache(organizationId: number): Promise<void> {
  const [purchaseOrdersCursor, salesOrdersCursor, paymentsCursor] = await Promise.all([
    getSyncCursor(organizationId, 'purchase_orders'),
    getSyncCursor(organizationId, 'sales_orders'),
    getSyncCursor(organizationId, 'payments'),
  ]);

  const [purchaseOrders, salesOrders, payments] = await Promise.all([
    fetchAllPurchaseOrders(organizationId, purchaseOrdersCursor),
    fetchAllSalesOrders(organizationId, salesOrdersCursor),
    fetchAllPayments(organizationId, paymentsCursor),
  ]);

  await Promise.all([
    upsertPurchaseOrders(organizationId, purchaseOrders),
    upsertSalesOrders(organizationId, salesOrders),
    upsertPayments(organizationId, payments),
  ]);

  const syncedAt = new Date().toISOString();
  await Promise.all([
    setSyncCursor(organizationId, 'purchase_orders', syncedAt),
    setSyncCursor(organizationId, 'sales_orders', syncedAt),
    setSyncCursor(organizationId, 'payments', syncedAt),
  ]);
}
