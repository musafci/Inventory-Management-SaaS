import type { PurchaseOrder, SalesOrder } from '@/src/api/types';
import { getDatabase } from '@/src/db/database.native';
import type { CachedOrderRow, OrderType } from '@/src/db/types';

function purchaseOrderToRow(organizationId: number, order: PurchaseOrder): CachedOrderRow {
  return {
    id: order.id,
    organization_id: organizationId,
    order_type: 'purchase_order',
    status: order.status,
    reference_number: order.po_number,
    total_amount: order.total_amount,
    partner_id: order.supplier_id,
    order_date: order.order_date,
    payload: JSON.stringify(order),
    updated_at: order.updated_at,
  };
}

function salesOrderToRow(organizationId: number, order: SalesOrder): CachedOrderRow {
  return {
    id: order.id,
    organization_id: organizationId,
    order_type: 'sales_order',
    status: order.status,
    reference_number: order.order_number,
    total_amount: order.total_amount,
    partner_id: order.customer_id,
    order_date: order.order_date,
    payload: JSON.stringify(order),
    updated_at: order.updated_at,
  };
}

async function upsertOrderRows(organizationId: number, rows: CachedOrderRow[]): Promise<void> {
  if (rows.length === 0) {
    return;
  }

  const db = await getDatabase();

  await db.withTransactionAsync(async () => {
    for (const row of rows) {
      await db.runAsync(
        `INSERT INTO cached_orders (
          id, organization_id, order_type, status, reference_number, total_amount,
          partner_id, order_date, payload, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (id, organization_id, order_type) DO UPDATE SET
          status = excluded.status,
          reference_number = excluded.reference_number,
          total_amount = excluded.total_amount,
          partner_id = excluded.partner_id,
          order_date = excluded.order_date,
          payload = excluded.payload,
          updated_at = excluded.updated_at`,
        row.id,
        organizationId,
        row.order_type,
        row.status,
        row.reference_number,
        row.total_amount,
        row.partner_id,
        row.order_date,
        row.payload,
        row.updated_at,
      );
    }
  });
}

export async function upsertPurchaseOrders(
  organizationId: number,
  orders: PurchaseOrder[],
): Promise<void> {
  await upsertOrderRows(
    organizationId,
    orders.map((order) => purchaseOrderToRow(organizationId, order)),
  );
}

export async function upsertSalesOrders(
  organizationId: number,
  orders: SalesOrder[],
): Promise<void> {
  await upsertOrderRows(
    organizationId,
    orders.map((order) => salesOrderToRow(organizationId, order)),
  );
}

function parseOrderPayload<T>(row: CachedOrderRow): T {
  return JSON.parse(row.payload) as T;
}

async function listCachedOrdersByType<T>(
  organizationId: number,
  orderType: OrderType,
  search?: string,
): Promise<T[]> {
  const db = await getDatabase();
  const rows = await db.getAllAsync<CachedOrderRow>(
    'SELECT * FROM cached_orders WHERE organization_id = ? AND order_type = ? ORDER BY order_date DESC, id DESC',
    organizationId,
    orderType,
  );

  let orders = rows.map((row) => parseOrderPayload<T>(row));

  if (search?.trim()) {
    const term = search.trim().toLowerCase();
    orders = orders.filter((order) => {
      const reference = orderType === 'purchase_order'
        ? (order as PurchaseOrder).po_number
        : (order as SalesOrder).order_number;

      return reference.toLowerCase().includes(term)
        || (order as PurchaseOrder | SalesOrder).status.toLowerCase().includes(term);
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

async function getCachedOrderByType<T>(
  organizationId: number,
  orderId: number,
  orderType: OrderType,
): Promise<T | null> {
  const db = await getDatabase();
  const row = await db.getFirstAsync<CachedOrderRow>(
    'SELECT * FROM cached_orders WHERE organization_id = ? AND id = ? AND order_type = ?',
    organizationId,
    orderId,
    orderType,
  );

  return row ? parseOrderPayload<T>(row) : null;
}

export async function getCachedPurchaseOrder(
  organizationId: number,
  orderId: number,
): Promise<PurchaseOrder | null> {
  return getCachedOrderByType<PurchaseOrder>(organizationId, orderId, 'purchase_order');
}

export async function getCachedSalesOrder(
  organizationId: number,
  orderId: number,
): Promise<SalesOrder | null> {
  return getCachedOrderByType<SalesOrder>(organizationId, orderId, 'sales_order');
}

export async function deleteCachedOrder(
  organizationId: number,
  orderId: number,
  orderType: OrderType,
): Promise<void> {
  const db = await getDatabase();
  await db.runAsync(
    'DELETE FROM cached_orders WHERE organization_id = ? AND id = ? AND order_type = ?',
    organizationId,
    orderId,
    orderType,
  );
}
