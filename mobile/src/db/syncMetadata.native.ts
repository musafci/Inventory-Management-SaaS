import { getDatabase } from '@/src/db/database.native';
import type { SyncResource } from '@/src/db/types';

export async function getSyncCursor(
  organizationId: number,
  resource: SyncResource,
): Promise<string | null> {
  const db = await getDatabase();
  const row = await db.getFirstAsync<{ cursor: string | null }>(
    'SELECT cursor FROM sync_metadata WHERE organization_id = ? AND resource = ?',
    organizationId,
    resource,
  );

  return row?.cursor ?? null;
}

export async function setSyncCursor(
  organizationId: number,
  resource: SyncResource,
  cursor: string | null,
): Promise<void> {
  const db = await getDatabase();
  const syncedAt = new Date().toISOString();

  await db.runAsync(
    `INSERT INTO sync_metadata (organization_id, resource, cursor, synced_at)
     VALUES (?, ?, ?, ?)
     ON CONFLICT (organization_id, resource)
     DO UPDATE SET cursor = excluded.cursor, synced_at = excluded.synced_at`,
    organizationId,
    resource,
    cursor,
    syncedAt,
  );
}

export async function getLastSyncedAt(
  organizationId: number,
  resource: SyncResource,
): Promise<string | null> {
  const db = await getDatabase();
  const row = await db.getFirstAsync<{ synced_at: string | null }>(
    'SELECT synced_at FROM sync_metadata WHERE organization_id = ? AND resource = ?',
    organizationId,
    resource,
  );

  return row?.synced_at ?? null;
}
