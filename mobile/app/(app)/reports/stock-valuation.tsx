import { Stack } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, View } from 'react-native';

import { WarehouseFilter } from '@/components/WarehouseFilter';
import {
  EmptyState,
  ErrorState,
  ListRow,
  LoadingState,
  MetricTile,
  PaginatedListScreen,
  ScreenContainer,
  SectionHeader,
} from '@/components/ui';
import { useWarehouses } from '@/src/hooks/useInventory';
import { useStockValuation } from '@/src/hooks/useReports';
import { theme } from '@/src/theme';

export default function StockValuationScreen() {
  const [warehouseId, setWarehouseId] = useState<number | null>(null);
  const warehousesQuery = useWarehouses();
  const query = useStockValuation(warehouseId);
  const report = query.data;
  const warehouses = warehousesQuery.data ?? [];

  return (
    <>
      <Stack.Screen options={{ title: 'Stock valuation' }} />
      {query.isLoading ? (
        <ScreenContainer><LoadingState /></ScreenContainer>
      ) : query.isError ? (
        <ScreenContainer><ErrorState message="Could not load stock valuation report." /></ScreenContainer>
      ) : report ? (
        <PaginatedListScreen
          data={report.by_warehouse}
          emptyMessage="No warehouse data."
          isLoading={false}
          isRefetching={query.isRefetching}
          keyExtractor={(item) => String(item.warehouse_id)}
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
              <View style={styles.metrics}>
                <MetricTile label="Total value" value={report.total_value} tone="indigo" />
                <MetricTile label="Total units" value={String(report.total_units)} tone="sky" />
              </View>
              <SectionHeader title="By warehouse" />
            </View>
          )}
          renderItem={(item) => (
            <ListRow
              meta={item.total_value}
              showChevron={false}
              subtitle={`${item.total_units} units`}
              title={item.warehouse_name}
            />
          )}
        />
      ) : (
        <ScreenContainer><EmptyState title="No report data available." /></ScreenContainer>
      )}
    </>
  );
}

const styles = StyleSheet.create({
  header: {
    paddingTop: theme.spacing.lg,
  },
  metrics: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: theme.spacing.md,
    marginBottom: theme.spacing.md,
    paddingHorizontal: theme.spacing.lg,
  },
});
