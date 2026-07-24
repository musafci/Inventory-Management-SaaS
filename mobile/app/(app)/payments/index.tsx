import { Stack } from 'expo-router';

import { ListRow, PaginatedListScreen } from '@/components/ui';

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
            showChevron
            subtitle={`${formatStatus(String(item.method))} · ${formatStatus(item.status)}\n${formatPayableType(item.payable_type)} #${item.payable_id}`}
            title={item.amount}
          />
        )}
      />
    </>
  );
}
