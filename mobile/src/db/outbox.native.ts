import { getDatabase } from '@/src/db/database.native';
import type { OutboxMutation, OutboxStatus } from '@/src/db/types';
import { outboxPlaceholder, resolveOutboxPlaceholder } from '@/src/sync/outboxPlaceholders';

type EnqueueMutationInput = {
  organizationId: number;
  method: string;
  path: string;
  body?: unknown;
  idempotencyKey?: string;
  dependsOnId?: number | null;
};

export async function enqueueMutation({
  organizationId,
  method,
  path,
  body,
  idempotencyKey,
  dependsOnId = null,
}: EnqueueMutationInput): Promise<number> {
  const db = await getDatabase();
  const now = new Date().toISOString();

  const result = await db.runAsync(
    `INSERT INTO outbox_mutations (
      organization_id, method, path, body, idempotency_key, depends_on_id, status, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?)`,
    organizationId,
    method,
    path,
    body !== undefined ? JSON.stringify(body) : null,
    idempotencyKey ?? null,
    dependsOnId,
    now,
    now,
  );

  return Number(result.lastInsertRowId);
}

export async function countPendingMutations(organizationId: number): Promise<number> {
  const db = await getDatabase();
  const row = await db.getFirstAsync<{ count: number }>(
    `SELECT COUNT(*) AS count FROM outbox_mutations
     WHERE organization_id = ? AND status IN ('pending', 'processing')`,
    organizationId,
  );

  return row?.count ?? 0;
}

export async function listPendingMutations(organizationId: number): Promise<OutboxMutation[]> {
  const db = await getDatabase();

  return db.getAllAsync<OutboxMutation>(
    `SELECT * FROM outbox_mutations
     WHERE organization_id = ? AND status = 'pending'
     ORDER BY id ASC`,
    organizationId,
  );
}

export async function listFailedMutations(organizationId: number): Promise<OutboxMutation[]> {
  const db = await getDatabase();

  return db.getAllAsync<OutboxMutation>(
    `SELECT * FROM outbox_mutations
     WHERE organization_id = ? AND status = 'failed'
     ORDER BY id ASC`,
    organizationId,
  );
}

export async function updateMutationStatus(
  id: number,
  status: OutboxStatus,
  errorMessage: string | null = null,
): Promise<void> {
  const db = await getDatabase();

  await db.runAsync(
    'UPDATE outbox_mutations SET status = ?, error_message = ?, updated_at = ? WHERE id = ?',
    status,
    errorMessage,
    new Date().toISOString(),
    id,
  );
}

export async function removeMutation(id: number): Promise<void> {
  const db = await getDatabase();
  await db.runAsync('DELETE FROM outbox_mutations WHERE id = ?', id);
}

export async function retryFailedMutation(id: number): Promise<void> {
  await updateMutationStatus(id, 'pending', null);
}

export async function replaceOutboxPlaceholderInPaths(
  organizationId: number,
  outboxId: number,
  entityId: number | string,
): Promise<void> {
  const db = await getDatabase();
  const placeholder = outboxPlaceholder(outboxId);
  const rows = await db.getAllAsync<OutboxMutation>(
    `SELECT * FROM outbox_mutations
     WHERE organization_id = ? AND status = 'pending' AND path LIKE ?`,
    organizationId,
    `%${placeholder}%`,
  );

  for (const row of rows) {
    await db.runAsync(
      'UPDATE outbox_mutations SET path = ?, updated_at = ? WHERE id = ?',
      resolveOutboxPlaceholder(row.path, outboxId, entityId),
      new Date().toISOString(),
      row.id,
    );
  }
}
