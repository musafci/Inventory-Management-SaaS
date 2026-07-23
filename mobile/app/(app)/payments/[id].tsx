import { Stack, useLocalSearchParams } from 'expo-router';
import {
  ActivityIndicator,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';

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
      <View style={styles.centered}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  if (!query.data) {
    return (
      <View style={styles.centered}>
        <Text style={styles.empty}>Payment not found.</Text>
      </View>
    );
  }

  const payment = query.data;

  return (
    <>
      <Stack.Screen options={{ title: `Payment #${payment.id}` }} />

      <ScrollView
        contentContainerStyle={styles.container}
        refreshControl={(
          <RefreshControl
            refreshing={query.isRefetching}
            onRefresh={() => {
              void query.refetch();
            }}
          />
        )}>
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
      </ScrollView>
    </>
  );
}

function DetailRow({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.row}>
      <Text style={styles.label}>{label}</Text>
      <Text style={styles.value}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  centered: {
    alignItems: 'center',
    flex: 1,
    justifyContent: 'center',
    padding: 24,
  },
  empty: {
    color: '#64748b',
    fontSize: 15,
  },
  container: {
    padding: 16,
    paddingBottom: 40,
  },
  row: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 10,
    borderWidth: 1,
    marginBottom: 10,
    padding: 14,
  },
  label: {
    color: '#64748b',
    fontSize: 13,
    marginBottom: 4,
  },
  value: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '600',
  },
});
