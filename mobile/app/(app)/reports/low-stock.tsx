import { Link, Stack } from 'expo-router';
import { useState } from 'react';
import {
  ActivityIndicator,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { OptimizedFlatList } from '@/components/OptimizedFlatList';
import { WarehouseFilter } from '@/components/WarehouseFilter';

import { useWarehouses } from '@/src/hooks/useInventory';
import { useLowStockReport } from '@/src/hooks/useReports';

export default function LowStockScreen() {
  const [warehouseId, setWarehouseId] = useState<number | null>(null);
  const warehousesQuery = useWarehouses();
  const query = useLowStockReport(warehouseId);
  const items = query.data ?? [];
  const warehouses = warehousesQuery.data ?? [];

  return (
    <>
      <Stack.Screen options={{ title: 'Low stock' }} />
      <View style={styles.container}>
        {query.isLoading ? (
          <View style={styles.centered}>
            <ActivityIndicator size="large" />
          </View>
        ) : query.isError ? (
          <View style={styles.centered}>
            <Text style={styles.error}>Could not load low stock report.</Text>
          </View>
        ) : (
          <OptimizedFlatList
            data={items}
            keyExtractor={(item) => String(item.stock_id)}
            refreshControl={(
              <RefreshControl
                refreshing={query.isRefetching}
                onRefresh={() => {
                  void query.refetch();
                }}
              />
            )}
            ListHeaderComponent={(
              <WarehouseFilter
                warehouses={warehouses}
                value={warehouseId}
                onChange={setWarehouseId}
              />
            )}
            ListEmptyComponent={(
              <View style={styles.centered}>
                <Text style={styles.empty}>No low stock items.</Text>
              </View>
            )}
            renderItem={({ item }) => (
              <View style={styles.row}>
                <View style={styles.rowBody}>
                  <Text style={styles.name}>{item.product_name}</Text>
                  <Text style={styles.meta}>
                    {item.sku} · {item.warehouse_name}
                  </Text>
                  <Text style={styles.meta}>
                    Qty: {item.quantity_available} · Reorder: {item.reorder_point}
                  </Text>
                </View>
                <Link href="/(app)/stocks" style={styles.link}>
                  Stocks
                </Link>
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
  centered: {
    alignItems: 'center',
    justifyContent: 'center',
    padding: 32,
  },
  error: {
    color: '#b91c1c',
    fontSize: 15,
  },
  empty: {
    color: '#64748b',
    fontSize: 15,
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
  link: {
    color: '#2563eb',
    fontSize: 14,
    fontWeight: '600',
  },
});
