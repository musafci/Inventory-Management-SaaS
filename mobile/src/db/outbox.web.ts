import { webMemoryStore } from '@/src/db/memoryStore.web';
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
  const now = new Date().toISOString();
  const id = webMemoryStore.nextOutboxId();

  webMemoryStore.outboxMutations.push({
    id,
    organization_id: organizationId,
    method,
    path,
    body: body !== undefined ? JSON.stringify(body) : null,
    idempotency_key: idempotencyKey ?? null,
    depends_on_id: dependsOnId,
    status: 'pending',
    error_message: null,
    created_at: now,
    updated_at: now,
  });

  return id;
}

export async function countPendingMutations(organizationId: number): Promise<number> {
  return webMemoryStore.outboxMutations.filter(
    (mutation) => mutation.organization_id === organizationId
      && (mutation.status === 'pending' || mutation.status === 'processing'),
  ).length;
}

export async function listPendingMutations(organizationId: number): Promise<OutboxMutation[]> {
  return webMemoryStore.outboxMutations.filter(
    (mutation) => mutation.organization_id === organizationId && mutation.status === 'pending',
  );
}

export async function listFailedMutations(organizationId: number): Promise<OutboxMutation[]> {
  return webMemoryStore.outboxMutations.filter(
    (mutation) => mutation.organization_id === organizationId && mutation.status === 'failed',
  );
}

export async function updateMutationStatus(
  id: number,
  status: OutboxStatus,
  errorMessage: string | null = null,
): Promise<void> {
  const mutation = webMemoryStore.outboxMutations.find((entry) => entry.id === id);

  if (!mutation) {
    return;
  }

  mutation.status = status;
  mutation.error_message = errorMessage;
  mutation.updated_at = new Date().toISOString();
}

export async function removeMutation(id: number): Promise<void> {
  const index = webMemoryStore.outboxMutations.findIndex((entry) => entry.id === id);

  if (index >= 0) {
    webMemoryStore.outboxMutations.splice(index, 1);
  }
}

export async function retryFailedMutation(id: number): Promise<void> {
  await updateMutationStatus(id, 'pending', null);
}

export async function replaceOutboxPlaceholderInPaths(
  organizationId: number,
  outboxId: number,
  entityId: number | string,
): Promise<void> {
  const placeholder = outboxPlaceholder(outboxId);

  for (const mutation of webMemoryStore.outboxMutations) {
    if (
      mutation.organization_id === organizationId
      && mutation.status === 'pending'
      && mutation.path.includes(placeholder)
    ) {
      mutation.path = resolveOutboxPlaceholder(mutation.path, outboxId, entityId);
      mutation.updated_at = new Date().toISOString();
    }
  }
}
