import { enqueueMutation } from '@/src/db/outbox';
import { generateIdempotencyKey } from '@/src/utils/idempotency';

type EnqueueOrExecuteInput<T> = {
  organizationId: number;
  isConnected: boolean;
  method: string;
  path: string;
  body?: unknown;
  idempotencyKey?: string;
  dependsOnId?: number | null;
  onlineFn: () => Promise<T>;
};

export async function enqueueOrExecute<T>({
  organizationId,
  isConnected,
  method,
  path,
  body,
  idempotencyKey,
  dependsOnId,
  onlineFn,
}: EnqueueOrExecuteInput<T>): Promise<T | null> {
  if (!isConnected) {
    await enqueueMutation({
      organizationId,
      method,
      path,
      body,
      idempotencyKey: idempotencyKey ?? generateIdempotencyKey(),
      dependsOnId: dependsOnId ?? null,
    });

    return null;
  }

  return onlineFn();
}
