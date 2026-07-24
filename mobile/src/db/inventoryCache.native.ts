import type { Stock, StockMovement, Warehouse } from '@/src/api/types';
import { getDatabase } from '@/src/db/database.native';
import { listCachedProducts } from '@/src/db/productsCache';

export async function upsertWarehouses(organizationId: number, items: Warehouse[]): Promise<void> {
  if (items.length === 0) {
    return;
  }

  const db = await getDatabase();

  await db.withTransactionAsync(async () => {
    for (const warehouse of items) {
      await db.runAsync(
        `INSERT INTO warehouses (id, organization_id, name, address, is_default, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON CONFLICT (id, organization_id) DO UPDATE SET
           name = excluded.name,
           address = excluded.address,
           is_default = excluded.is_default,
           created_at = excluded.created_at,
           updated_at = excluded.updated_at`,
        warehouse.id,
        organizationId,
        warehouse.name,
        warehouse.address,
        warehouse.is_default ? 1 : 0,
        warehouse.created_at,
        warehouse.updated_at,
      );
    }
  });
}

export async function upsertStocks(organizationId: number, items: Stock[]): Promise<void> {
  if (items.length === 0) {
    return;
  }

  const db = await getDatabase();

  await db.withTransactionAsync(async () => {
    for (const stock of items) {
      await db.runAsync(
        `INSERT INTO stocks (
          id, organization_id, warehouse_id, product_id,
          quantity_on_hand, quantity_reserved, quantity_available,
          last_counted_at, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (id, organization_id) DO UPDATE SET
          warehouse_id = excluded.warehouse_id,
          product_id = excluded.product_id,
          quantity_on_hand = excluded.quantity_on_hand,
          quantity_reserved = excluded.quantity_reserved,
          quantity_available = excluded.quantity_available,
          last_counted_at = excluded.last_counted_at,
          created_at = excluded.created_at,
          updated_at = excluded.updated_at`,
        stock.id,
        organizationId,
        stock.warehouse_id,
        stock.product_id,
        stock.quantity_on_hand,
        stock.quantity_reserved,
        stock.quantity_available,
        stock.last_counted_at,
        stock.created_at,
        stock.updated_at,
      );
    }
  });
}

export async function upsertStockMovements(
  organizationId: number,
  items: StockMovement[],
): Promise<void> {
  if (items.length === 0) {
    return;
  }

  const db = await getDatabase();

  await db.withTransactionAsync(async () => {
    for (const movement of items) {
      await db.runAsync(
        `INSERT INTO stock_movements (
          id, organization_id, warehouse_id, product_id, type, quantity, note,
          reference_type, reference_id, created_by, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (id, organization_id) DO UPDATE SET
          warehouse_id = excluded.warehouse_id,
          product_id = excluded.product_id,
          type = excluded.type,
          quantity = excluded.quantity,
          note = excluded.note,
          reference_type = excluded.reference_type,
          reference_id = excluded.reference_id,
          created_by = excluded.created_by,
          created_at = excluded.created_at,
          updated_at = excluded.updated_at`,
        movement.id,
        organizationId,
        movement.warehouse_id,
        movement.product_id,
        movement.type,
        movement.quantity,
        movement.note,
        movement.reference_type,
        movement.reference_id,
        movement.created_by,
        movement.created_at,
        movement.updated_at,
      );
    }
  });
}

export async function listCachedWarehouses(organizationId: number): Promise<Warehouse[]> {
  const db = await getDatabase();
  const rows = await db.getAllAsync<{
    id: number;
    organization_id: number;
    name: string;
    address: string | null;
    is_default: number;
    created_at: string | null;
    updated_at: string | null;
  }>(
    'SELECT * FROM warehouses WHERE organization_id = ? ORDER BY name COLLATE NOCASE ASC',
    organizationId,
  );

  return rows.map((row) => ({
    id: row.id,
    organization_id: row.organization_id,
    name: row.name,
    address: row.address,
    is_default: row.is_default === 1,
    created_at: row.created_at,
    updated_at: row.updated_at,
  }));
}

export async function deleteCachedWarehouse(
  organizationId: number,
  warehouseId: number,
): Promise<void> {
  const db = await getDatabase();
  await db.runAsync(
    'DELETE FROM warehouses WHERE organization_id = ? AND id = ?',
    organizationId,
    warehouseId,
  );
}

export async function listCachedStocks(organizationId: number, search?: string): Promise<Stock[]> {
  const db = await getDatabase();
  const rows = await db.getAllAsync<Stock>(
    'SELECT * FROM stocks WHERE organization_id = ? ORDER BY quantity_on_hand DESC',
    organizationId,
  );

  if (!search?.trim()) {
    return rows;
  }

  const products = await listCachedProducts(organizationId, search);

  if (products.length === 0) {
    return [];
  }

  const productIds = new Set(products.map((product) => product.id));

  return rows.filter((stock) => productIds.has(stock.product_id));
}

export async function listCachedStockMovements(organizationId: number): Promise<StockMovement[]> {
  const db = await getDatabase();

  return db.getAllAsync<StockMovement>(
    'SELECT * FROM stock_movements WHERE organization_id = ? ORDER BY created_at DESC',
    organizationId,
  );
}
