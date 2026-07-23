import {
  fetchAllStockMovements,
  fetchAllStocks,
  fetchWarehouses,
} from '@/src/api/inventory';
import {
  upsertStockMovements,
  upsertStocks,
  upsertWarehouses,
} from '@/src/db/inventoryCache';
import { getSyncCursor, setSyncCursor } from '@/src/db/syncMetadata';
import { pullCatalogCache } from '@/src/sync/pullCatalog';
import { pullOrdersCache } from '@/src/sync/pullOrders';
import { pullPartnersCache } from '@/src/sync/pullPartners';

export async function pullInventoryCache(organizationId: number): Promise<void> {
  const [warehousesCursor, stocksCursor, movementsCursor] = await Promise.all([
    getSyncCursor(organizationId, 'warehouses'),
    getSyncCursor(organizationId, 'stocks'),
    getSyncCursor(organizationId, 'stock_movements'),
  ]);

  const [warehouses, stocks, movements] = await Promise.all([
    fetchWarehouses({ organizationId, perPage: 200, updatedAfter: warehousesCursor ?? undefined }),
    fetchAllStocks(organizationId, stocksCursor),
    fetchAllStockMovements(organizationId, movementsCursor),
  ]);

  await Promise.all([
    upsertWarehouses(organizationId, warehouses),
    upsertStocks(organizationId, stocks),
    upsertStockMovements(organizationId, movements),
  ]);

  const syncedAt = new Date().toISOString();
  await Promise.all([
    setSyncCursor(organizationId, 'warehouses', syncedAt),
    setSyncCursor(organizationId, 'stocks', syncedAt),
    setSyncCursor(organizationId, 'stock_movements', syncedAt),
  ]);
}

export async function pullAllCaches(organizationId: number): Promise<void> {
  await pullCatalogCache(organizationId);
  await pullInventoryCache(organizationId);
  await pullPartnersCache(organizationId);
  await pullOrdersCache(organizationId);
}
