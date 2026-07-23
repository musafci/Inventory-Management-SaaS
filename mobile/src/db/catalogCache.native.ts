import { getDatabase } from '@/src/db/database.native';
import type { CategoryRow, UnitRow } from '@/src/db/types';
import type { Category, Unit } from '@/src/api/types';

export async function upsertCategories(organizationId: number, categories: Category[]): Promise<void> {
  if (categories.length === 0) {
    return;
  }

  const db = await getDatabase();

  await db.withTransactionAsync(async () => {
    for (const category of categories) {
      await db.runAsync(
        `INSERT INTO categories (id, organization_id, parent_id, name, slug, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON CONFLICT (id, organization_id) DO UPDATE SET
           parent_id = excluded.parent_id,
           name = excluded.name,
           slug = excluded.slug,
           created_at = excluded.created_at,
           updated_at = excluded.updated_at`,
        category.id,
        organizationId,
        category.parent_id,
        category.name,
        category.slug,
        category.created_at,
        category.updated_at,
      );
    }
  });
}

export async function upsertUnits(organizationId: number, units: Unit[]): Promise<void> {
  if (units.length === 0) {
    return;
  }

  const db = await getDatabase();

  await db.withTransactionAsync(async () => {
    for (const unit of units) {
      await db.runAsync(
        `INSERT INTO units (id, organization_id, name, symbol, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?)
         ON CONFLICT (id, organization_id) DO UPDATE SET
           name = excluded.name,
           symbol = excluded.symbol,
           created_at = excluded.created_at,
           updated_at = excluded.updated_at`,
        unit.id,
        organizationId,
        unit.name,
        unit.symbol,
        unit.created_at,
        unit.updated_at,
      );
    }
  });
}

export async function listCachedCategories(organizationId: number): Promise<Category[]> {
  const db = await getDatabase();
  const rows = await db.getAllAsync<CategoryRow>(
    'SELECT * FROM categories WHERE organization_id = ? ORDER BY name COLLATE NOCASE ASC',
    organizationId,
  );

  return rows.map((row) => ({
    id: row.id,
    organization_id: row.organization_id,
    parent_id: row.parent_id,
    name: row.name,
    slug: row.slug ?? '',
    created_at: row.created_at,
    updated_at: row.updated_at,
  }));
}

export async function listCachedUnits(organizationId: number): Promise<Unit[]> {
  const db = await getDatabase();
  const rows = await db.getAllAsync<UnitRow>(
    'SELECT * FROM units WHERE organization_id = ? ORDER BY name COLLATE NOCASE ASC',
    organizationId,
  );

  return rows.map((row) => ({
    id: row.id,
    organization_id: row.organization_id,
    name: row.name,
    symbol: row.symbol ?? '',
    created_at: row.created_at,
    updated_at: row.updated_at,
  }));
}

export async function deleteCachedCategory(organizationId: number, categoryId: number): Promise<void> {
  const db = await getDatabase();
  await db.runAsync(
    'DELETE FROM categories WHERE organization_id = ? AND id = ?',
    organizationId,
    categoryId,
  );
}

export async function deleteCachedUnit(organizationId: number, unitId: number): Promise<void> {
  const db = await getDatabase();
  await db.runAsync(
    'DELETE FROM units WHERE organization_id = ? AND id = ?',
    organizationId,
    unitId,
  );
}
