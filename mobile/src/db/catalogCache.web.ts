import type { Category, Unit } from '@/src/api/types';
import { webMemoryStore } from '@/src/db/memoryStore.web';

export async function upsertCategories(organizationId: number, items: Category[]): Promise<void> {
  for (const category of items) {
    webMemoryStore.categories.set(
      webMemoryStore.categoryKey(organizationId, category.id),
      { ...category, organization_id: organizationId },
    );
  }
}

export async function upsertUnits(organizationId: number, items: Unit[]): Promise<void> {
  for (const unit of items) {
    webMemoryStore.units.set(
      webMemoryStore.unitKey(organizationId, unit.id),
      { ...unit, organization_id: organizationId },
    );
  }
}

export async function listCachedCategories(organizationId: number): Promise<Category[]> {
  const prefix = `${organizationId}:`;

  return [...webMemoryStore.categories.entries()]
    .filter(([key]) => key.startsWith(prefix))
    .map(([, category]) => category)
    .sort((a, b) => a.name.localeCompare(b.name));
}

export async function listCachedUnits(organizationId: number): Promise<Unit[]> {
  const prefix = `${organizationId}:`;

  return [...webMemoryStore.units.entries()]
    .filter(([key]) => key.startsWith(prefix))
    .map(([, unit]) => unit)
    .sort((a, b) => a.name.localeCompare(b.name));
}

export async function deleteCachedCategory(organizationId: number, categoryId: number): Promise<void> {
  webMemoryStore.categories.delete(webMemoryStore.categoryKey(organizationId, categoryId));
}

export async function deleteCachedUnit(organizationId: number, unitId: number): Promise<void> {
  webMemoryStore.units.delete(webMemoryStore.unitKey(organizationId, unitId));
}
