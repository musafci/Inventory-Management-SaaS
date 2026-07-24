import {
  extractCreatedEntityId,
  isDuplicateOrderActionError,
  outboxPlaceholder,
  resolveOutboxPlaceholder,
} from '@/src/sync/outboxPlaceholders';

describe('outboxPlaceholders', () => {
  it('builds and resolves placeholders', () => {
    expect(outboxPlaceholder(5)).toBe('__outbox_5__');
    expect(resolveOutboxPlaceholder('/v1/sales-orders/__outbox_5__/confirm', 5, 42))
      .toBe('/v1/sales-orders/42/confirm');
  });

  it('extracts created entity id from order responses', () => {
    expect(extractCreatedEntityId('POST', '/v1/sales-orders', { id: 99 })).toBe(99);
    expect(extractCreatedEntityId('GET', '/v1/sales-orders', { id: 99 })).toBeNull();
  });

  it('detects duplicate order action errors', () => {
    expect(isDuplicateOrderActionError(409, 'Conflict')).toBe(true);
    expect(isDuplicateOrderActionError(422, 'Order is already confirmed')).toBe(true);
    expect(isDuplicateOrderActionError(422, 'Insufficient stock')).toBe(false);
  });
});
