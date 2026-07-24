export function outboxPlaceholder(outboxId: number): string {
  return `__outbox_${outboxId}__`;
}

export function pathWithOutboxPlaceholder(path: string, outboxId: number): string {
  return path.replace('__ENTITY__', outboxPlaceholder(outboxId));
}

export function resolveOutboxPlaceholder(path: string, outboxId: number, entityId: number | string): string {
  return path.replaceAll(outboxPlaceholder(outboxId), String(entityId));
}

export function extractCreatedEntityId(method: string, path: string, response: unknown): number | null {
  if (method !== 'POST') {
    return null;
  }

  if (!path.endsWith('/purchase-orders') && !path.endsWith('/sales-orders')) {
    return null;
  }

  if (response && typeof response === 'object' && 'id' in response) {
    const id = Number((response as { id: number }).id);

    return Number.isFinite(id) ? id : null;
  }

  return null;
}

export function isDuplicateOrderActionError(status: number, message: string): boolean {
  if (status === 409) {
    return true;
  }

  if (status !== 422) {
    return false;
  }

  const normalized = message.toLowerCase();

  return normalized.includes('already')
    || normalized.includes('cannot')
    || normalized.includes('invalid status');
}
