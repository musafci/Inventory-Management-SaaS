import type { Category, Product, Unit } from '@/src/api/types';
import type { OutboxMutation, SyncResource } from '@/src/db/types';

type SyncEntry = {
  cursor: string | null;
  synced_at: string | null;
};

const products = new Map<string, Product>();
const categories = new Map<string, Category>();
const units = new Map<string, Unit>();
const syncMetadata = new Map<string, SyncEntry>();
const outboxMutations: OutboxMutation[] = [];
let nextOutboxId = 1;

function productKey(organizationId: number, productId: number): string {
  return `${organizationId}:${productId}`;
}

function categoryKey(organizationId: number, categoryId: number): string {
  return `${organizationId}:${categoryId}`;
}

function unitKey(organizationId: number, unitId: number): string {
  return `${organizationId}:${unitId}`;
}

function syncKey(organizationId: number, resource: SyncResource): string {
  return `${organizationId}:${resource}`;
}

export const webMemoryStore = {
  products,
  categories,
  units,
  syncMetadata,
  outboxMutations,
  nextOutboxId: () => nextOutboxId++,
  setNextOutboxId(value: number) {
    nextOutboxId = value;
  },
  productKey,
  categoryKey,
  unitKey,
  syncKey,
};

import { clearInventoryMemoryCache } from '@/src/db/inventoryCache.web';

export function clearOrganizationMemoryCache(organizationId: number): void {
  for (const key of products.keys()) {
    if (key.startsWith(`${organizationId}:`)) {
      products.delete(key);
    }
  }

  for (const key of categories.keys()) {
    if (key.startsWith(`${organizationId}:`)) {
      categories.delete(key);
    }
  }

  for (const key of units.keys()) {
    if (key.startsWith(`${organizationId}:`)) {
      units.delete(key);
    }
  }

  for (const key of syncMetadata.keys()) {
    if (key.startsWith(`${organizationId}:`)) {
      syncMetadata.delete(key);
    }
  }

  for (let index = outboxMutations.length - 1; index >= 0; index -= 1) {
    if (outboxMutations[index]?.organization_id === organizationId) {
      outboxMutations.splice(index, 1);
    }
  }

  clearInventoryMemoryCache(organizationId);
}
