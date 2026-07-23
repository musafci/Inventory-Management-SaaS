import { getDatabase } from '@/src/db/database.native';
import type { OutboxMutation, OutboxStatus } from '@/src/db/types';

type EnqueueMutationInput = {
  organizationId: number;
  method: string;
  path: string;
  body?: unknown;
  idempotencyKey?: string;
};

export async function enqueueMutation({
  organizationId,
  method,
  path,
  body,
  idempotencyKey,
}: EnqueueMutationInput): Promise<number> {
  const db = await getDatabase();
  const now = new Date().toISOString();

  const result = await db.runAsync(
    `INSERT INTO outbox_mutations (
      organization_id, method, path, body, idempotency_key, status, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)`,
    organizationId,
    method,
    path,
    body !== undefined ? JSON.stringify(body) : null,
    idempotencyKey ?? null,
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
