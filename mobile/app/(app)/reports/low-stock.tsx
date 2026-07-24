import { Stack } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, View } from 'react-native';

import { WarehouseFilter } from '@/components/WarehouseFilter';
import {
  ErrorState,
  ListRow,
  LoadingState,
  PaginatedListScreen,
  ScreenContainer,
} from '@/components/ui';
import { useWarehouses } from '@/src/hooks/useInventory';
import { useLowStockReport } from '@/src/hooks/useReports';
import { theme } from '@/src/theme';

export default function LowStockScreen() {
  const [warehouseId, setWarehouseId] = useState<number | null>(null);
  const warehousesQuery = useWarehouses();
  const query = useLowStockReport(warehouseId);
  const items = query.data ?? [];
  const warehouses = warehousesQuery.data ?? [];

  return (
    <>
      <Stack.Screen options={{ title: 'Low stock' }} />
      {query.isLoading ? (
        <ScreenContainer><LoadingState /></ScreenContainer>
      ) : query.isError ? (
        <ScreenContainer><ErrorState message="Could not load low stock report." /></ScreenContainer>
      ) : (
        <PaginatedListScreen
          data={items}
          emptyMessage="No low stock items."
          isLoading={false}
          isRefetching={query.isRefetching}
          keyExtractor={(item) => String(item.stock_id)}
          onRefresh={() => {
            void query.refetch();
          }}
          ListHeaderComponent={(
            <View style={styles.header}>
              <WarehouseFilter
                warehouses={warehouses}
                value={warehouseId}
                onChange={setWarehouseId}
              />
            </View>
          )}
          renderItem={(item) => (
            <ListRow
              href="/(app)/stocks"
              meta="Stocks"
              showChevron
              subtitle={`${item.sku} · ${item.warehouse_name} · Qty: ${item.quantity_available} · Reorder: ${item.reorder_point}`}
              title={item.product_name}
            />
          )}
        />
      )}
    </>
  );
}

const styles = StyleSheet.create({
  header: {
    paddingTop: theme.spacing.lg,
  },
});
