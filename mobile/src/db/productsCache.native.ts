import { getDatabase } from '@/src/db/database.native';
import type { ProductRow } from '@/src/db/types';
import type { Product } from '@/src/api/types';

function toRow(product: Product): Omit<ProductRow, 'organization_id'> & { organization_id: number } {
  return {
    id: product.id,
    organization_id: product.organization_id,
    category_id: product.category_id,
    unit_id: product.unit_id,
    name: product.name,
    sku: product.sku,
    barcode: product.barcode,
    cost_price: product.cost_price,
    selling_price: product.selling_price,
    tax_rate: product.tax_rate,
    reorder_point: product.reorder_point,
    is_active: product.is_active ? 1 : 0,
    created_at: product.created_at,
    updated_at: product.updated_at,
  };
}

export function rowToProduct(row: ProductRow): Product {
  return {
    id: row.id,
    organization_id: row.organization_id,
    category_id: row.category_id ?? 0,
    unit_id: row.unit_id ?? 0,
    name: row.name,
    sku: row.sku,
    barcode: row.barcode,
    cost_price: row.cost_price ?? '0',
    selling_price: row.selling_price ?? '0',
    tax_rate: row.tax_rate ?? '0',
    reorder_point: row.reorder_point,
    is_active: row.is_active === 1,
    created_at: row.created_at,
    updated_at: row.updated_at,
  };
}

export async function upsertProducts(organizationId: number, products: Product[]): Promise<void> {
  if (products.length === 0) {
    return;
  }

  const db = await getDatabase();

  await db.withTransactionAsync(async () => {
    for (const product of products) {
      const row = toRow(product);

      await db.runAsync(
        `INSERT INTO products (
          id, organization_id, category_id, unit_id, name, sku, barcode,
          cost_price, selling_price, tax_rate, reorder_point, is_active, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (id, organization_id) DO UPDATE SET
          category_id = excluded.category_id,
          unit_id = excluded.unit_id,
          name = excluded.name,
          sku = excluded.sku,
          barcode = excluded.barcode,
          cost_price = excluded.cost_price,
          selling_price = excluded.selling_price,
          tax_rate = excluded.tax_rate,
          reorder_point = excluded.reorder_point,
          is_active = excluded.is_active,
          created_at = excluded.created_at,
          updated_at = excluded.updated_at`,
        row.id,
        organizationId,
        row.category_id,
        row.unit_id,
        row.name,
        row.sku,
        row.barcode,
        row.cost_price,
        row.selling_price,
        row.tax_rate,
        row.reorder_point,
        row.is_active,
        row.created_at,
        row.updated_at,
      );
    }
  });
}

export async function getCachedProduct(
  organizationId: number,
  productId: number,
): Promise<Product | null> {
  const db = await getDatabase();
  const row = await db.getFirstAsync<ProductRow>(
    'SELECT * FROM products WHERE organization_id = ? AND id = ?',
    organizationId,
    productId,
  );

  return row ? rowToProduct(row) : null;
}

export async function listCachedProducts(
  organizationId: number,
  search?: string,
): Promise<Product[]> {
  const db = await getDatabase();

  if (search?.trim()) {
    const term = `%${search.trim()}%`;
    const rows = await db.getAllAsync<ProductRow>(
      `SELECT * FROM products
       WHERE organization_id = ?
         AND (name LIKE ? OR sku LIKE ? OR barcode LIKE ?)
       ORDER BY name COLLATE NOCASE ASC`,
      organizationId,
      term,
      term,
      term,
    );

    return rows.map(rowToProduct);
  }

  const rows = await db.getAllAsync<ProductRow>(
    'SELECT * FROM products WHERE organization_id = ? ORDER BY name COLLATE NOCASE ASC',
    organizationId,
  );

  return rows.map(rowToProduct);
}

export async function deleteCachedProduct(organizationId: number, productId: number): Promise<void> {
  const db = await getDatabase();
  await db.runAsync(
    'DELETE FROM products WHERE organization_id = ? AND id = ?',
    organizationId,
    productId,
  );
}
