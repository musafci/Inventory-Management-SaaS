import { type Href, Stack, useLocalSearchParams } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import {
  Card,
  DetailRow,
  EmptyState,
  ListRow,
  LoadingState,
  ScreenContainer,
  ScreenScrollView,
  StatusBadge,
} from '@/components/ui';
import type { Payment } from '@/src/api/types';
import { usePayment } from '@/src/hooks/usePayments';
import { theme } from '@/src/theme';

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

function paymentStatusTone(status: string): 'default' | 'success' | 'warning' | 'danger' | 'info' {
  if (status.includes('fail')) return 'danger';
  if (status.includes('refund')) return 'warning';
  if (status.includes('complete') || status.includes('paid')) return 'success';
  if (status.includes('pending')) return 'warning';
  return 'info';
}

function payableHref(payment: Payment): Href | null {
  if (payment.payable_type.includes('PurchaseOrder')) {
    return `/(app)/purchase-orders/${payment.payable_id}` as Href;
  }

  if (payment.payable_type.includes('SalesOrder')) {
    return `/(app)/sales-orders/${payment.payable_id}` as Href;
  }

  return null;
}

export default function PaymentDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const paymentId = Number(id);
  const query = usePayment(Number.isFinite(paymentId) ? paymentId : null);

  if (query.isLoading) {
    return (
      <ScreenContainer>
        <Stack.Screen options={{ title: 'Payment' }} />
        <LoadingState />
      </ScreenContainer>
    );
  }

  if (!query.data) {
    return (
      <ScreenContainer>
        <Stack.Screen options={{ title: 'Payment' }} />
        <EmptyState title="Payment not found." />
      </ScreenContainer>
    );
  }

  const payment = query.data;
  const orderHref = payableHref(payment);
  const payableLabel = `${formatPayableType(payment.payable_type)} #${payment.payable_id}`;

  return (
    <>
      <Stack.Screen options={{ title: `Payment #${payment.id}` }} />

      <ScreenScrollView
        refreshing={query.isRefetching}
        onRefresh={() => {
          void query.refetch();
        }}>
        <View style={styles.hero}>
          <Text style={styles.amount}>{payment.amount}</Text>
          <StatusBadge label={formatStatus(payment.status)} tone={paymentStatusTone(payment.status)} />
        </View>

        <Card>
          <DetailRow label="Method" value={formatStatus(String(payment.method))} />
          <DetailRow label="Reference" value={payment.reference ?? '—'} />
          <DetailRow label="Note" value={payment.note ?? '—'} />
          <DetailRow label="Paid at" value={payment.paid_at ?? '—'} />
          {payment.recorded_by ? (
            <DetailRow label="Recorded by" value={`User #${payment.recorded_by}`} />
          ) : null}
        </Card>

        {orderHref ? (
          <ListRow
            href={orderHref}
            showChevron
            subtitle="View linked order"
            title={payableLabel}
          />
        ) : (
          <Card>
            <DetailRow label="Payable" value={payableLabel} />
          </Card>
        )}
      </ScreenScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  hero: {
    alignItems: 'flex-start',
    gap: theme.spacing.sm,
    marginBottom: theme.spacing.lg,
  },
  amount: {
    ...theme.typography.metric,
    color: theme.colors.primary,
  },
});
