import { Link, Stack } from 'expo-router';
import { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';

import { OptimizedFlatList } from '@/components/OptimizedFlatList';

import { useAuth } from '@/src/auth/AuthContext';
import { useProductsList, useProducts } from '@/src/hooks/useProducts';
import { canCreateInventory } from '@/src/permissions';

export default function ProductsScreen() {
  const { permissions } = useAuth();
  const [search, setSearch] = useState('');
  const query = useProducts(search);
  const products = useProductsList(search);

  const emptyMessage = useMemo(() => {
    if (query.isLoading) {
      return null;
    }

    if (search.trim()) {
      return 'No products match your search.';
    }

    return 'No products yet.';
  }, [query.isLoading, search]);

  return (
    <>
      <Stack.Screen
        options={{
          title: 'Products',
          headerRight: () => (
            canCreateInventory(permissions) ? (
              <Link href="/(app)/products/new" style={styles.headerLink}>
                Add
              </Link>
            ) : null
          ),
        }}
      />

      <View style={styles.container}>
        <TextInput
          accessibilityLabel="Search products"
          value={search}
          onChangeText={setSearch}
          placeholder="Search name, SKU, or barcode"
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
            data={products}
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
              <Link href={`/(app)/products/${item.id}`} asChild>
                <Pressable style={styles.row}>
                  <View style={styles.rowBody}>
                    <Text style={styles.name}>{item.name}</Text>
                    <Text style={styles.meta}>
                      {item.sku ? `SKU ${item.sku}` : 'No SKU'}
                      {item.barcode ? ` · ${item.barcode}` : ''}
                    </Text>
                  </View>
                  <Text style={styles.price}>{item.selling_price}</Text>
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
  price: {
    color: '#0f172a',
    fontSize: 15,
    fontWeight: '700',
  },
  footerLoader: {
    marginVertical: 16,
  },
});
