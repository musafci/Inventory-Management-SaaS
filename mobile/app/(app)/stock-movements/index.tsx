import { Link, Stack } from 'expo-router';
import {
  ActivityIndicator,
  FlatList,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { useAuth } from '@/src/auth/AuthContext';
import { useInventoryLabels, useStockMovements, useStockMovementsList } from '@/src/hooks/useInventory';
import { canUpdateInventory } from '@/src/permissions';

function formatMovementType(type: string): string {
  return type.replace(/_/g, ' ');
}

function formatDate(value: string | null): string {
  if (!value) {
    return '—';
  }

  return new Date(value).toLocaleString();
}

export default function StockMovementsScreen() {
  const { permissions } = useAuth();
  const query = useStockMovements();
  const movements = useStockMovementsList();
  const labelsQuery = useInventoryLabels();
  const labels = labelsQuery.data;

  return (
    <>
      <Stack.Screen
        options={{
          title: 'Stock movements',
          headerRight: () => (
            canUpdateInventory(permissions) ? (
              <Link href="/(app)/stock-movements/new" style={styles.headerLink}>
                Adjust
              </Link>
            ) : null
          ),
        }}
      />

      <View style={styles.container}>
        {query.isLoading ? (
          <View style={styles.centered}>
            <ActivityIndicator size="large" />
          </View>
        ) : (
          <FlatList
            data={movements}
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
                <Text style={styles.empty}>No stock movements yet.</Text>
              </View>
            )}
            ListFooterComponent={
              query.isFetchingNextPage ? (
                <ActivityIndicator style={styles.footerLoader} />
              ) : null
            }
            renderItem={({ item }) => (
              <View style={styles.row}>
                <View style={styles.rowBody}>
                  <Text style={styles.title}>
                    {formatMovementType(item.type)} · {item.quantity}
                  </Text>
                  <Text style={styles.meta}>
                    {labels?.products.get(item.product_id) ?? `Product #${item.product_id}`}
                    {' · '}
                    {labels?.warehouses.get(item.warehouse_id) ?? `Warehouse #${item.warehouse_id}`}
                  </Text>
                  {item.note ? <Text style={styles.note}>{item.note}</Text> : null}
                </View>
                <Text style={styles.date}>{formatDate(item.created_at)}</Text>
              </View>
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
    backgroundColor: '#fff',
    borderBottomColor: '#e2e8f0',
    borderBottomWidth: 1,
    paddingHorizontal: 16,
    paddingVertical: 14,
  },
  rowBody: {
    marginBottom: 6,
  },
  title: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '600',
    textTransform: 'capitalize',
  },
  meta: {
    color: '#64748b',
    fontSize: 13,
    marginTop: 4,
  },
  note: {
    color: '#475569',
    fontSize: 13,
    marginTop: 6,
  },
  date: {
    color: '#94a3b8',
    fontSize: 12,
  },
  footerLoader: {
    marginVertical: 16,
  },
});
