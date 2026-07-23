import { webMemoryStore } from '@/src/db/memoryStore.web';
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
  const now = new Date().toISOString();
  const id = webMemoryStore.nextOutboxId();

  webMemoryStore.outboxMutations.push({
    id,
    organization_id: organizationId,
    method,
    path,
    body: body !== undefined ? JSON.stringify(body) : null,
    idempotency_key: idempotencyKey ?? null,
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
