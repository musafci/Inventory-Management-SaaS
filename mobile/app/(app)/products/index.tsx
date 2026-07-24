import { Stack } from 'expo-router';
import { useMemo, useState } from 'react';

import {
  HeaderAction,
  ListRow,
  PaginatedListScreen,
} from '@/components/ui';

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
              <HeaderAction href="/(app)/products/new" label="Add" />
            ) : null
          ),
        }}
      />

      <PaginatedListScreen
        data={products}
        emptyMessage={emptyMessage}
        hasNextPage={query.hasNextPage}
        isFetchingNextPage={query.isFetchingNextPage}
        isLoading={query.isLoading}
        isRefetching={query.isRefetching}
        keyExtractor={(item) => String(item.id)}
        renderItem={(item) => (
          <ListRow
            href={`/(app)/products/${item.id}`}
            meta={item.selling_price}
            subtitle={
              `${item.sku ? `SKU ${item.sku}` : 'No SKU'}${item.barcode ? ` · ${item.barcode}` : ''}`
            }
            title={item.name}
          />
        )}
        search={search}
        searchAccessibilityLabel="Search products"
        searchPlaceholder="Search name, SKU, or barcode"
        onEndReached={() => {
          void query.fetchNextPage();
        }}
        onRefresh={() => {
          void query.refetch();
        }}
        onSearchChange={setSearch}
      />
    </>
  );
}
