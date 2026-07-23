import type { Product } from '@/src/api/types';
import { webMemoryStore } from '@/src/db/memoryStore.web';

export async function upsertProducts(organizationId: number, items: Product[]): Promise<void> {
  for (const product of items) {
    webMemoryStore.products.set(
      webMemoryStore.productKey(organizationId, product.id),
      { ...product, organization_id: organizationId },
    );
  }
}

export async function getCachedProduct(
  organizationId: number,
  productId: number,
): Promise<Product | null> {
  return webMemoryStore.products.get(webMemoryStore.productKey(organizationId, productId)) ?? null;
}

export async function listCachedProducts(
  organizationId: number,
  search?: string,
): Promise<Product[]> {
  const prefix = `${organizationId}:`;
  const items = [...webMemoryStore.products.entries()]
    .filter(([key]) => key.startsWith(prefix))
    .map(([, product]) => product);

  if (!search?.trim()) {
    return items.sort((a, b) => a.name.localeCompare(b.name));
  }

  const term = search.trim().toLowerCase();

  return items
    .filter((product) => (
      product.name.toLowerCase().includes(term)
      || product.sku?.toLowerCase().includes(term)
      || product.barcode?.toLowerCase().includes(term)
    ))
    .sort((a, b) => a.name.localeCompare(b.name));
}

export async function deleteCachedProduct(organizationId: number, productId: number): Promise<void> {
  webMemoryStore.products.delete(webMemoryStore.productKey(organizationId, productId));
}
