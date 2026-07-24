import { Stack, useLocalSearchParams } from 'expo-router';

import { Card, DetailRow, ErrorState, LoadingState, ScreenScrollView } from '@/components/ui';
import { usePayment } from '@/src/hooks/usePayments';

function formatPayableType(payableType: string): string {
  if (payableType.includes('PurchaseOrder')) {
    return 'Purchase order';
  }

  if (payableType.includes('SalesOrder')) {
    return 'Sales order';
  }

  return payableType.split('\\').pop() ?? payableType;
}

function formatStatus(status: string): string {
  return status.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

export default function PaymentDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const paymentId = Number(id);
  const query = usePayment(Number.isFinite(paymentId) ? paymentId : null);

  if (query.isLoading) {
    return (
      <>
        <Stack.Screen options={{ title: 'Payment' }} />
        <LoadingState />
      </>
    );
  }

  if (!query.data) {
    return (
      <>
        <Stack.Screen options={{ title: 'Payment' }} />
        <ErrorState message="Payment not found." />
      </>
    );
  }

  const payment = query.data;

  return (
    <>
      <Stack.Screen options={{ title: `Payment #${payment.id}` }} />

      <ScreenScrollView
        refreshing={query.isRefetching}
        onRefresh={() => {
          void query.refetch();
        }}>
        <Card>
          <DetailRow label="Amount" value={payment.amount} />
          <DetailRow label="Method" value={formatStatus(String(payment.method))} />
          <DetailRow label="Status" value={formatStatus(payment.status)} />
          <DetailRow
            label="Payable"
            value={`${formatPayableType(payment.payable_type)} #${payment.payable_id}`}
          />
          <DetailRow label="Reference" value={payment.reference ?? '—'} />
          <DetailRow label="Note" value={payment.note ?? '—'} />
          <DetailRow label="Paid at" value={payment.paid_at ?? '—'} />
        </Card>
      </ScreenScrollView>
    </>
  );
}
