import { fetchAllCategories, fetchAllUnits } from '@/src/api/catalog';
import { fetchAllProducts } from '@/src/api/products';
import { upsertCategories, upsertUnits } from '@/src/db/catalogCache';
import { upsertProducts } from '@/src/db/productsCache';
import { getSyncCursor, setSyncCursor } from '@/src/db/syncMetadata';

export async function pullCatalogCache(organizationId: number): Promise<void> {
  const [productsCursor, categoriesCursor, unitsCursor] = await Promise.all([
    getSyncCursor(organizationId, 'products'),
    getSyncCursor(organizationId, 'categories'),
    getSyncCursor(organizationId, 'units'),
  ]);

  const [products, categories, units] = await Promise.all([
    fetchAllProducts(organizationId, productsCursor),
    fetchAllCategories(organizationId, categoriesCursor),
    fetchAllUnits(organizationId, unitsCursor),
  ]);

  await Promise.all([
    upsertProducts(organizationId, products),
    upsertCategories(organizationId, categories),
    upsertUnits(organizationId, units),
  ]);

  const syncedAt = new Date().toISOString();
  await Promise.all([
    setSyncCursor(organizationId, 'products', syncedAt),
    setSyncCursor(organizationId, 'categories', syncedAt),
    setSyncCursor(organizationId, 'units', syncedAt),
  ]);
}
