import { ApiError, apiRequest } from '@/src/api/client';
import {
  listFailedMutations,
  listPendingMutations,
  removeMutation,
  replaceOutboxPlaceholderInPaths,
  updateMutationStatus,
} from '@/src/db/outbox';
import type { OutboxMutation } from '@/src/db/types';
import {
  extractCreatedEntityId,
  isDuplicateOrderActionError,
} from '@/src/sync/outboxPlaceholders';

function dependencyIsPending(mutation: OutboxMutation, pendingIds: Set<number>): boolean {
  return mutation.depends_on_id !== null && pendingIds.has(mutation.depends_on_id);
}

async function executeMutation(
  mutation: OutboxMutation,
  organizationId: number,
): Promise<'success' | 'failed' | 'skipped'> {
  if (mutation.depends_on_id !== null) {
    const pending = await listPendingMutations(organizationId);
    const pendingIds = new Set(pending.map((entry) => entry.id));

    if (dependencyIsPending(mutation, pendingIds)) {
      return 'skipped';
    }
  }

  await updateMutationStatus(mutation.id, 'processing');

  try {
    const response = await apiRequest(mutation.path, {
      method: mutation.method,
      body: mutation.body ? JSON.parse(mutation.body) : undefined,
      organizationId,
      idempotencyKey: mutation.idempotency_key ?? undefined,
    });

    const createdEntityId = extractCreatedEntityId(mutation.method, mutation.path, response);

    if (createdEntityId !== null) {
      await replaceOutboxPlaceholderInPaths(organizationId, mutation.id, createdEntityId);
    }

    await removeMutation(mutation.id);

    return 'success';
  } catch (error) {
    if (error instanceof ApiError && isDuplicateOrderActionError(error.status, error.message)) {
      await removeMutation(mutation.id);

      return 'success';
    }

    const message = error instanceof ApiError ? error.message : 'Sync failed.';
    await updateMutationStatus(mutation.id, 'failed', message);

    return 'failed';
  }
}

export async function processOutbox(organizationId: number): Promise<void> {
  let progress = true;

  while (progress) {
    progress = false;
    const pending = await listPendingMutations(organizationId);

    for (const mutation of pending) {
      const result = await executeMutation(mutation, organizationId);

      if (result === 'success') {
        progress = true;
      }

      if (result === 'failed') {
        break;
      }
    }
  }
}

export async function listAllFailedMutations(organizationId: number): Promise<OutboxMutation[]> {
  return listFailedMutations(organizationId);
}
