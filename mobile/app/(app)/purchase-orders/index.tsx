import { Link, Stack } from 'expo-router';
import { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';

import { useAuth } from '@/src/auth/AuthContext';
import { usePurchaseOrders, usePurchaseOrdersList } from '@/src/hooks/useOrders';
import { canCreatePurchaseOrder } from '@/src/permissions';

function formatStatus(status: string): string {
  return status.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

export default function PurchaseOrdersScreen() {
  const { permissions } = useAuth();
  const [search, setSearch] = useState('');
  const query = usePurchaseOrders(search);
  const orders = usePurchaseOrdersList(search);

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
              <Link href="/(app)/purchase-orders/new" style={styles.headerLink}>
                Add
              </Link>
            ) : null
          ),
        }}
      />

      <View style={styles.container}>
        <TextInput
          value={search}
          onChangeText={setSearch}
          placeholder="Search PO number or supplier"
          style={styles.search}
          autoCapitalize="none"
          clearButtonMode="while-editing"
        />

        {query.isLoading ? (
          <View style={styles.centered}>
            <ActivityIndicator size="large" />
          </View>
        ) : (
          <FlatList
            data={orders}
            keyExtractor={(item) => String(item.id)}
            refreshControl={(
              <RefreshControl
                refreshing={query.isRefetching}
                onRefresh={() => {
                  void query.refetch();
                }}
              />
            )}
            onEndReached={() => {
              if (query.hasNextPage && !query.isFetchingNextPage) {
                void query.fetchNextPage();
              }
            }}
            onEndReachedThreshold={0.4}
            ListEmptyComponent={(
              <View style={styles.centered}>
                <Text style={styles.empty}>{emptyMessage}</Text>
              </View>
            )}
            ListFooterComponent={
              query.isFetchingNextPage ? (
                <ActivityIndicator style={styles.footerLoader} />
              ) : null
            }
            renderItem={({ item }) => (
              <Link href={`/(app)/purchase-orders/${item.id}`} asChild>
                <Pressable style={styles.row}>
                  <View style={styles.rowBody}>
                    <Text style={styles.name}>{item.po_number}</Text>
                    <Text style={styles.meta}>
                      {formatStatus(item.status)}
                      {item.supplier?.name ? ` · ${item.supplier.name}` : ''}
                    </Text>
                  </View>
                  <Text style={styles.amount}>{item.total_amount}</Text>
                </Pressable>
              </Link>
            )}
          />
        )}
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f8fafc',
    flex: 1,
  },
  headerLink: {
    color: '#2563eb',
    fontSize: 16,
    fontWeight: '600',
    marginRight: 16,
  },
  search: {
    backgroundColor: '#fff',
    borderColor: '#cbd5e1',
    borderRadius: 10,
    borderWidth: 1,
    fontSize: 16,
    margin: 16,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  centered: {
    alignItems: 'center',
    justifyContent: 'center',
    padding: 32,
  },
  empty: {
    color: '#64748b',
    fontSize: 15,
    textAlign: 'center',
  },
  row: {
    alignItems: 'center',
    backgroundColor: '#fff',
    borderBottomColor: '#e2e8f0',
    borderBottomWidth: 1,
    flexDirection: 'row',
    paddingHorizontal: 16,
    paddingVertical: 14,
  },
  rowBody: {
    flex: 1,
    paddingRight: 12,
  },
  name: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '600',
  },
  meta: {
    color: '#64748b',
    fontSize: 13,
    marginTop: 4,
  },
  amount: {
    color: '#0f172a',
    fontSize: 15,
    fontWeight: '700',
  },
  footerLoader: {
    marginVertical: 16,
  },
});
