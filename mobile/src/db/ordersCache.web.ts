import type { PurchaseOrder, SalesOrder } from '@/src/api/types';
import type { OrderType } from '@/src/db/types';
import { webOrdersStore } from '@/src/db/memoryStore.web';

function orderKey(organizationId: number, orderType: OrderType, orderId: number): string {
  return `${organizationId}:${orderType}:${orderId}`;
}

export async function upsertPurchaseOrders(
  organizationId: number,
  orders: PurchaseOrder[],
): Promise<void> {
  for (const order of orders) {
    webOrdersStore.orders.set(
      orderKey(organizationId, 'purchase_order', order.id),
      { ...order, organization_id: organizationId },
    );
  }
}

export async function upsertSalesOrders(
  organizationId: number,
  orders: SalesOrder[],
): Promise<void> {
  for (const order of orders) {
    webOrdersStore.orders.set(
      orderKey(organizationId, 'sales_order', order.id),
      { ...order, organization_id: organizationId },
    );
  }
}

function listCachedOrdersByType<T extends PurchaseOrder | SalesOrder>(
  organizationId: number,
  orderType: OrderType,
  search?: string,
): T[] {
  const prefix = `${organizationId}:${orderType}:`;
  let orders = [...webOrdersStore.orders.entries()]
    .filter(([key]) => key.startsWith(prefix))
    .map(([, order]) => order as T)
    .sort((a, b) => {
      const dateCompare = (b.order_date ?? '').localeCompare(a.order_date ?? '');

      return dateCompare !== 0 ? dateCompare : b.id - a.id;
    });

  if (search?.trim()) {
    const term = search.trim().toLowerCase();
    orders = orders.filter((order) => {
      const reference = orderType === 'purchase_order'
        ? (order as PurchaseOrder).po_number
        : (order as SalesOrder).order_number;

      return reference.toLowerCase().includes(term)
        || order.status.toLowerCase().includes(term);
    });
  }

  return orders;
}

export async function listCachedPurchaseOrders(
  organizationId: number,
  search?: string,
): Promise<PurchaseOrder[]> {
  return listCachedOrdersByType<PurchaseOrder>(organizationId, 'purchase_order', search);
}

export async function listCachedSalesOrders(
  organizationId: number,
  search?: string,
): Promise<SalesOrder[]> {
  return listCachedOrdersByType<SalesOrder>(organizationId, 'sales_order', search);
}

export async function getCachedPurchaseOrder(
  organizationId: number,
  orderId: number,
): Promise<PurchaseOrder | null> {
  return (webOrdersStore.orders.get(orderKey(organizationId, 'purchase_order', orderId)) as PurchaseOrder | undefined) ?? null;
}

export async function getCachedSalesOrder(
  organizationId: number,
  orderId: number,
): Promise<SalesOrder | null> {
  return (webOrdersStore.orders.get(orderKey(organizationId, 'sales_order', orderId)) as SalesOrder | undefined) ?? null;
}

export async function deleteCachedOrder(
  organizationId: number,
  orderId: number,
  orderType: OrderType,
): Promise<void> {
  webOrdersStore.orders.delete(orderKey(organizationId, orderType, orderId));
}
