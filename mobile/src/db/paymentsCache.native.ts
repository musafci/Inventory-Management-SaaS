import type { Payment } from '@/src/api/types';
import { getDatabase } from '@/src/db/database.native';
import type { PaymentRow } from '@/src/db/types';

function paymentToRow(organizationId: number, payment: Payment): PaymentRow {
  return {
    id: payment.id,
    organization_id: organizationId,
    payable_type: payment.payable_type,
    payable_id: payment.payable_id,
    amount: payment.amount,
    method: payment.method,
    status: payment.status,
    reference: payment.reference,
    note: payment.note,
    paid_at: payment.paid_at,
    payload: JSON.stringify(payment),
    updated_at: payment.updated_at,
  };
}

function rowToPayment(row: PaymentRow): Payment {
  return JSON.parse(row.payload) as Payment;
}

export async function upsertPayments(organizationId: number, payments: Payment[]): Promise<void> {
  if (payments.length === 0) {
    return;
  }

  const db = await getDatabase();

  await db.withTransactionAsync(async () => {
    for (const payment of payments) {
      const row = paymentToRow(organizationId, payment);

      await db.runAsync(
        `INSERT INTO payments (
          id, organization_id, payable_type, payable_id, amount, method, status,
          reference, note, paid_at, payload, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (id, organization_id) DO UPDATE SET
          payable_type = excluded.payable_type,
          payable_id = excluded.payable_id,
          amount = excluded.amount,
          method = excluded.method,
          status = excluded.status,
          reference = excluded.reference,
          note = excluded.note,
          paid_at = excluded.paid_at,
          payload = excluded.payload,
          updated_at = excluded.updated_at`,
        row.id,
        organizationId,
        row.payable_type,
        row.payable_id,
        row.amount,
        row.method,
        row.status,
        row.reference,
        row.note,
        row.paid_at,
        row.payload,
        row.updated_at,
      );
    }
  });
}

export async function listCachedPayments(organizationId: number): Promise<Payment[]> {
  const db = await getDatabase();
  const rows = await db.getAllAsync<PaymentRow>(
    'SELECT * FROM payments WHERE organization_id = ? ORDER BY paid_at DESC, id DESC',
    organizationId,
  );

  return rows.map(rowToPayment);
}

export async function getCachedPayment(
  organizationId: number,
  paymentId: number,
): Promise<Payment | null> {
  const db = await getDatabase();
  const row = await db.getFirstAsync<PaymentRow>(
    'SELECT * FROM payments WHERE organization_id = ? AND id = ?',
    organizationId,
    paymentId,
  );

  return row ? rowToPayment(row) : null;
}
