import type { Payment } from '@/src/api/types';
import { webPaymentsStore } from '@/src/db/memoryStore.web';

export async function upsertPayments(organizationId: number, payments: Payment[]): Promise<void> {
  for (const payment of payments) {
    webPaymentsStore.payments.set(
      webPaymentsStore.paymentKey(organizationId, payment.id),
      { ...payment, organization_id: organizationId },
    );
  }
}

export async function listCachedPayments(organizationId: number): Promise<Payment[]> {
  const prefix = `${organizationId}:`;

  return [...webPaymentsStore.payments.entries()]
    .filter(([key]) => key.startsWith(prefix))
    .map(([, payment]) => payment)
    .sort((a, b) => (b.paid_at ?? '').localeCompare(a.paid_at ?? ''));
}

export async function getCachedPayment(
  organizationId: number,
  paymentId: number,
): Promise<Payment | null> {
  return webPaymentsStore.payments.get(webPaymentsStore.paymentKey(organizationId, paymentId)) ?? null;
}
