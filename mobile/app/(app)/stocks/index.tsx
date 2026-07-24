import { Stack } from 'expo-router';
import { useMemo, useState } from 'react';

import { ListRow, PaginatedListScreen } from '@/components/ui';

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

      <PaginatedListScreen
        data={stocks}
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
          <ListRow
            meta={String(item.quantity_on_hand)}
            showChevron={false}
            subtitle={`${labels?.warehouses.get(item.warehouse_id) ?? `Warehouse #${item.warehouse_id}`} · ${item.quantity_available} avail.`}
            title={labels?.products.get(item.product_id) ?? `Product #${item.product_id}`}
          />
        )}
        search={search}
        searchAccessibilityLabel="Search stock levels"
        searchPlaceholder="Search product or warehouse"
      />
    </>
  );
}
