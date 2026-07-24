import { Stack } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, View } from 'react-native';

import {
  Button,
  Card,
  EmptyState,
  ErrorState,
  Input,
  ListRow,
  LoadingState,
  MetricTile,
  PaginatedListScreen,
  ScreenContainer,
  SectionHeader,
} from '@/components/ui';
import { usePurchaseSummary } from '@/src/hooks/useReports';
import { theme } from '@/src/theme';

export default function PurchaseSummaryScreen() {
  const [orderFrom, setOrderFrom] = useState('');
  const [orderTo, setOrderTo] = useState('');
  const [appliedFilters, setAppliedFilters] = useState<{ orderFrom?: string; orderTo?: string }>({});

  const query = usePurchaseSummary(appliedFilters);
  const report = query.data;

  const applyFilters = () => {
    setAppliedFilters({
      orderFrom: orderFrom.trim() || undefined,
      orderTo: orderTo.trim() || undefined,
    });
  };

  return (
    <>
      <Stack.Screen options={{ title: 'Purchase summary' }} />
      <Card style={styles.filters}>
        <Input
          autoCapitalize="none"
          label="Order from (YYYY-MM-DD)"
          placeholder="2026-01-01"
          value={orderFrom}
          onChangeText={setOrderFrom}
        />
        <Input
          autoCapitalize="none"
          label="Order to (YYYY-MM-DD)"
          placeholder="2026-12-31"
          value={orderTo}
          onChangeText={setOrderTo}
        />
        <Button label="Apply filters" onPress={applyFilters} />
      </Card>

      {query.isLoading ? (
        <ScreenContainer><LoadingState /></ScreenContainer>
      ) : query.isError ? (
        <ScreenContainer><ErrorState message="Could not load purchase summary." /></ScreenContainer>
      ) : report ? (
        <PaginatedListScreen
          data={report.by_status}
          emptyMessage="No status data."
          isLoading={false}
          isRefetching={query.isRefetching}
          keyExtractor={(item) => item.status}
          onRefresh={() => {
            void query.refetch();
          }}
          ListHeaderComponent={(
            <View style={styles.header}>
              <View style={styles.metrics}>
                <MetricTile label="Orders" value={String(report.order_count)} tone="sky" />
                <MetricTile label="Total amount" value={report.total_amount} tone="indigo" />
                <MetricTile label="Payments received" value={report.payments_received} tone="emerald" />
              </View>
              <SectionHeader title="By status" />
            </View>
          )}
          renderItem={(item) => (
            <ListRow
              meta={item.total_amount}
              showChevron={false}
              subtitle={`${item.order_count} orders`}
              title={item.status.replace(/_/g, ' ')}
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
  filters: {
    marginHorizontal: theme.spacing.xl,
    marginTop: theme.spacing.lg,
  },
  header: {
    paddingTop: theme.spacing.md,
  },
  metrics: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: theme.spacing.md,
    marginBottom: theme.spacing.md,
    paddingHorizontal: theme.spacing.lg,
  },
});
