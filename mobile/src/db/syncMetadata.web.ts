import { webMemoryStore } from '@/src/db/memoryStore.web';
import type { SyncResource } from '@/src/db/types';

export async function getSyncCursor(
  organizationId: number,
  resource: SyncResource,
): Promise<string | null> {
  return webMemoryStore.syncMetadata.get(webMemoryStore.syncKey(organizationId, resource))?.cursor
    ?? null;
}

export async function setSyncCursor(
  organizationId: number,
  resource: SyncResource,
  cursor: string | null,
): Promise<void> {
  webMemoryStore.syncMetadata.set(webMemoryStore.syncKey(organizationId, resource), {
    cursor,
    synced_at: new Date().toISOString(),
  });
}

export async function getLastSyncedAt(
  organizationId: number,
  resource: SyncResource,
): Promise<string | null> {
  return webMemoryStore.syncMetadata.get(webMemoryStore.syncKey(organizationId, resource))?.synced_at
    ?? null;
}
