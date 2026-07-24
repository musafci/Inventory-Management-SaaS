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
import { useDeletePurchaseOrder, usePurchaseOrders, usePurchaseOrdersList } from '@/src/hooks/useOrders';
import { canCreatePurchaseOrder, canDeletePurchaseOrder } from '@/src/permissions';
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

export default function PurchaseOrdersScreen() {
  const { permissions } = useAuth();
  const [search, setSearch] = useState('');
  const query = usePurchaseOrders(search);
  const orders = usePurchaseOrdersList(search);
  const deleteMutation = useDeletePurchaseOrder();

  const handleDelete = (orderId: number, poNumber: string) => {
    Alert.alert('Delete purchase order', `Delete ${poNumber}?`, [
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
      return 'No purchase orders match your search.';
    }

    return 'No purchase orders yet.';
  }, [query.isLoading, search]);

  return (
    <>
      <Stack.Screen
        options={{
          title: 'Purchase orders',
          headerRight: () => (
            canCreatePurchaseOrder(permissions) ? (
              <HeaderAction href="/(app)/purchase-orders/new" label="Add" />
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
                href={`/(app)/purchase-orders/${item.id}`}
                meta={item.total_amount}
                right={(
                  <StatusBadge
                    label={formatStatus(item.status)}
                    tone={orderStatusTone(item.status)}
                  />
                )}
                showChevron
                subtitle={item.supplier?.name ?? undefined}
                title={item.po_number}
              />
            </View>
            {canDeletePurchaseOrder(permissions) ? (
              <TextAction
                label="Delete"
                onPress={() => handleDelete(item.id, item.po_number)}
                tone="danger"
              />
            ) : null}
          </View>
        )}
        search={search}
        searchAccessibilityLabel="Search purchase orders"
        searchPlaceholder="Search PO number or supplier"
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
