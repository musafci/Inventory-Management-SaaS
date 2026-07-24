import type { SQLiteDatabase } from 'expo-sqlite';
import { openDatabaseAsync } from 'expo-sqlite';

import { MIGRATION_SQL, SCHEMA_VERSION } from '@/src/db/schema';

const DATABASE_NAME = 'inventory.db';

let databasePromise: Promise<SQLiteDatabase> | null = null;

async function migrate(db: SQLiteDatabase): Promise<void> {
  await db.execAsync(MIGRATION_SQL);

  const row = await db.getFirstAsync<{ version: number }>(
    'SELECT version FROM schema_version LIMIT 1',
  );

  if (!row) {
    await db.runAsync('INSERT INTO schema_version (version) VALUES (?)', SCHEMA_VERSION);
    return;
  }

  if (row.version < 4) {
    const columns = await db.getAllAsync<{ name: string }>(
      'PRAGMA table_info(outbox_mutations)',
    );
    const hasDependsOn = columns.some((column) => column.name === 'depends_on_id');

    if (!hasDependsOn) {
      await db.execAsync('ALTER TABLE outbox_mutations ADD COLUMN depends_on_id INTEGER');
    }

    await db.runAsync('UPDATE schema_version SET version = ?', SCHEMA_VERSION);
  }
}

export async function getDatabase(): Promise<SQLiteDatabase> {
  if (!databasePromise) {
    databasePromise = (async () => {
      const db = await openDatabaseAsync(DATABASE_NAME);
      await migrate(db);

      return db;
    })();
  }

  return databasePromise;
}

export async function clearOrganizationCache(organizationId: number): Promise<void> {
  const db = await getDatabase();

  await db.withTransactionAsync(async () => {
    await db.runAsync('DELETE FROM products WHERE organization_id = ?', organizationId);
    await db.runAsync('DELETE FROM categories WHERE organization_id = ?', organizationId);
    await db.runAsync('DELETE FROM units WHERE organization_id = ?', organizationId);
    await db.runAsync('DELETE FROM warehouses WHERE organization_id = ?', organizationId);
    await db.runAsync('DELETE FROM stocks WHERE organization_id = ?', organizationId);
    await db.runAsync('DELETE FROM stock_movements WHERE organization_id = ?', organizationId);
    await db.runAsync('DELETE FROM suppliers WHERE organization_id = ?', organizationId);
    await db.runAsync('DELETE FROM customers WHERE organization_id = ?', organizationId);
    await db.runAsync('DELETE FROM cached_orders WHERE organization_id = ?', organizationId);
    await db.runAsync('DELETE FROM payments WHERE organization_id = ?', organizationId);
    await db.runAsync('DELETE FROM sync_metadata WHERE organization_id = ?', organizationId);
    await db.runAsync('DELETE FROM outbox_mutations WHERE organization_id = ?', organizationId);
  });
}
