import { Stack } from 'expo-router';
import { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';

import { OptimizedFlatList } from '@/components/OptimizedFlatList';

import { useInventoryLabels, useStocks, useStocksList } from '@/src/hooks/useInventory';

export default function StocksScreen() {
  const [search, setSearch] = useState('');
  const query = useStocks(search);
  const stocks = useStocksList(search);
  const labelsQuery = useInventoryLabels();

  const emptyMessage = useMemo(() => {
    if (query.isLoading) {
      return null;
    }

    return search.trim() ? 'No stock rows match your search.' : 'No stock levels yet.';
  }, [query.isLoading, search]);

  const labels = labelsQuery.data;

  return (
    <>
      <Stack.Screen options={{ title: 'Stock levels' }} />

      <View style={styles.container}>
        <TextInput
          value={search}
          onChangeText={setSearch}
          placeholder="Search product or warehouse"
          style={styles.search}
          autoCapitalize="none"
          clearButtonMode="while-editing"
        />

        {query.isLoading ? (
          <View style={styles.centered}>
            <ActivityIndicator size="large" />
          </View>
        ) : (
          <OptimizedFlatList
            data={stocks}
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
              <View style={styles.row}>
                <View style={styles.rowBody}>
                  <Text style={styles.name}>
                    {labels?.products.get(item.product_id) ?? `Product #${item.product_id}`}
                  </Text>
                  <Text style={styles.meta}>
                    {labels?.warehouses.get(item.warehouse_id) ?? `Warehouse #${item.warehouse_id}`}
                  </Text>
                </View>
                <View style={styles.qtyBlock}>
                  <Text style={styles.qty}>{item.quantity_on_hand}</Text>
                  <Text style={styles.qtyMeta}>{item.quantity_available} avail.</Text>
                </View>
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
  qtyBlock: {
    alignItems: 'flex-end',
  },
  qty: {
    color: '#0f172a',
    fontSize: 18,
    fontWeight: '700',
  },
  qtyMeta: {
    color: '#64748b',
    fontSize: 12,
    marginTop: 2,
  },
  footerLoader: {
    marginVertical: 16,
  },
});
