import { Stack } from 'expo-router';
import { useMemo, useState } from 'react';
import { Alert, StyleSheet, View } from 'react-native';

import {
  HeaderAction,
  ListRow,
  PaginatedListScreen,
  StatusBadge,
  TextAction,
} from '@/components/ui';

import { ApiError } from '@/src/api/client';
import { useAuth } from '@/src/auth/AuthContext';
import { useDeleteSalesOrder, useSalesOrders, useSalesOrdersList } from '@/src/hooks/useOrders';
import { canCreateSalesOrder, canDeleteSalesOrder } from '@/src/permissions';
import { theme } from '@/src/theme';

function formatStatus(status: string): string {
  return status.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function orderStatusTone(status: string): 'default' | 'success' | 'warning' | 'danger' | 'info' {
  if (status.includes('cancel')) return 'danger';
  if (status.includes('deliver') || status.includes('received')) return 'success';
  if (status.includes('draft')) return 'default';
  if (status.includes('partial')) return 'warning';
  return 'info';
}

export default function SalesOrdersScreen() {
  const { permissions } = useAuth();
  const [search, setSearch] = useState('');
  const query = useSalesOrders(search);
  const orders = useSalesOrdersList(search);
  const deleteMutation = useDeleteSalesOrder();

  const handleDelete = (orderId: number, orderNumber: string) => {
    Alert.alert('Delete sales order', `Delete ${orderNumber}?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Delete',
        style: 'destructive',
        onPress: () => {
          void (async () => {
            try {
              await deleteMutation.mutateAsync(orderId);
            } catch (error) {
              const message = error instanceof ApiError ? error.message : 'Could not delete order.';
              Alert.alert('Delete failed', message);
            }
          })();
        },
      },
    ]);
  };

  const emptyMessage = useMemo(() => {
    if (query.isLoading) {
      return null;
    }

    if (search.trim()) {
      return 'No sales orders match your search.';
    }

    return 'No sales orders yet.';
  }, [query.isLoading, search]);

  return (
    <>
      <Stack.Screen
        options={{
          title: 'Sales orders',
          headerRight: () => (
            canCreateSalesOrder(permissions) ? (
              <HeaderAction href="/(app)/sales-orders/new" label="Add" />
            ) : null
          ),
        }}
      />

      <PaginatedListScreen
        data={orders}
        emptyMessage={emptyMessage}
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
        onSearchChange={setSearch}
        renderItem={(item) => (
          <View style={styles.row}>
            <View style={styles.rowMain}>
              <ListRow
                href={`/(app)/sales-orders/${item.id}`}
                meta={item.total_amount}
                right={(
                  <StatusBadge
                    label={formatStatus(item.status)}
                    tone={orderStatusTone(item.status)}
                  />
                )}
                showChevron
                subtitle={item.customer?.name ?? undefined}
                title={item.order_number}
              />
            </View>
            {canDeleteSalesOrder(permissions) ? (
              <TextAction
                label="Delete"
                onPress={() => handleDelete(item.id, item.order_number)}
                tone="danger"
              />
            ) : null}
          </View>
        )}
        search={search}
        searchAccessibilityLabel="Search sales orders"
        searchPlaceholder="Search order number or customer"
      />
    </>
  );
}

const styles = StyleSheet.create({
  row: {
    alignItems: 'center',
    flexDirection: 'row',
    paddingRight: theme.spacing.lg,
  },
  rowMain: {
    flex: 1,
  },
});
