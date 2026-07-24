import type { Stock, StockMovement, Warehouse } from '@/src/api/types';
import { listCachedProducts } from '@/src/db/productsCache';
import { webMemoryStore } from '@/src/db/memoryStore.web';

function warehouseKey(organizationId: number, warehouseId: number): string {
  return `${organizationId}:${warehouseId}`;
}

function stockKey(organizationId: number, stockId: number): string {
  return `${organizationId}:${stockId}`;
}

function movementKey(organizationId: number, movementId: number): string {
  return `${organizationId}:${movementId}`;
}

const warehouses = new Map<string, Warehouse>();
const stocks = new Map<string, Stock>();
const stockMovements = new Map<string, StockMovement>();

export const webInventoryStore = {
  warehouses,
  stocks,
  stockMovements,
  warehouseKey,
  stockKey,
  movementKey,
};

export async function upsertWarehouses(organizationId: number, items: Warehouse[]): Promise<void> {
  for (const warehouse of items) {
    warehouses.set(warehouseKey(organizationId, warehouse.id), {
      ...warehouse,
      organization_id: organizationId,
    });
  }
}

export async function upsertStocks(organizationId: number, items: Stock[]): Promise<void> {
  for (const stock of items) {
    stocks.set(stockKey(organizationId, stock.id), { ...stock, organization_id: organizationId });
  }
}

export async function upsertStockMovements(
  organizationId: number,
  items: StockMovement[],
): Promise<void> {
  for (const movement of items) {
    stockMovements.set(movementKey(organizationId, movement.id), {
      ...movement,
      organization_id: organizationId,
    });
  }
}

export async function listCachedWarehouses(organizationId: number): Promise<Warehouse[]> {
  const prefix = `${organizationId}:`;

  return [...warehouses.entries()]
    .filter(([key]) => key.startsWith(prefix))
    .map(([, warehouse]) => warehouse)
    .sort((a, b) => a.name.localeCompare(b.name));
}

export async function deleteCachedWarehouse(
  organizationId: number,
  warehouseId: number,
): Promise<void> {
  warehouses.delete(warehouseKey(organizationId, warehouseId));
}

export async function listCachedStocks(organizationId: number, search?: string): Promise<Stock[]> {
  const prefix = `${organizationId}:`;
  let items = [...stocks.entries()]
    .filter(([key]) => key.startsWith(prefix))
    .map(([, stock]) => stock);

  if (search?.trim()) {
    const products = await listCachedProducts(organizationId, search);
    const productIds = new Set(products.map((product) => product.id));
    items = items.filter((stock) => productIds.has(stock.product_id));
  }

  return items.sort((a, b) => b.quantity_on_hand - a.quantity_on_hand);
}

export async function listCachedStockMovements(organizationId: number): Promise<StockMovement[]> {
  const prefix = `${organizationId}:`;

  return [...stockMovements.entries()]
    .filter(([key]) => key.startsWith(prefix))
    .map(([, movement]) => movement)
    .sort((a, b) => (b.created_at ?? '').localeCompare(a.created_at ?? ''));
}

export function clearInventoryMemoryCache(organizationId: number): void {
  const prefix = `${organizationId}:`;

  for (const key of warehouses.keys()) {
    if (key.startsWith(prefix)) {
      warehouses.delete(key);
    }
  }

  for (const key of stocks.keys()) {
    if (key.startsWith(prefix)) {
      stocks.delete(key);
    }
  }

  for (const key of stockMovements.keys()) {
    if (key.startsWith(prefix)) {
      stockMovements.delete(key);
    }
  }
}
