import { Stack } from 'expo-router';

import { ListRow, PaginatedListScreen, StatusBadge } from '@/components/ui';

import { usePayments, usePaymentsList } from '@/src/hooks/usePayments';

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

export default function PaymentsScreen() {
  const query = usePayments();
  const payments = usePaymentsList();

  return (
    <>
      <Stack.Screen options={{ title: 'Payments' }} />

      <PaginatedListScreen
        data={payments}
        emptyMessage="No payments yet."
        hasNextPage={query.hasNextPage}
        isFetchingNextPage={query.isFetchingNextPage}
        isLoading={query.isLoading}
        isRefetching={query.isRefetching}
        keyExtractor={(item) => String(item.id)}
        onEndReached={() => {
          void query.fetchNextPage();
        }}
        onRefresh={() => {
          void query.refetch();
        }}
        renderItem={(item) => (
          <ListRow
            href={`/(app)/payments/${item.id}`}
            right={
              <StatusBadge
                label={formatStatus(item.status)}
                tone={paymentStatusTone(item.status)}
              />
            }
            showChevron
            subtitle={`${formatStatus(String(item.method))} · ${formatPayableType(item.payable_type)} #${item.payable_id}`}
            title={item.amount}
          />
        )}
      />
    </>
  );
}
