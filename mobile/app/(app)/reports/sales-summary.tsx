import { Stack } from 'expo-router';
import { useState } from 'react';
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

import { useSalesSummary } from '@/src/hooks/useReports';

export default function SalesSummaryScreen() {
  const [orderFrom, setOrderFrom] = useState('');
  const [orderTo, setOrderTo] = useState('');
  const [appliedFilters, setAppliedFilters] = useState<{ orderFrom?: string; orderTo?: string }>({});

  const query = useSalesSummary(appliedFilters);
  const report = query.data;

  const applyFilters = () => {
    setAppliedFilters({
      orderFrom: orderFrom.trim() || undefined,
      orderTo: orderTo.trim() || undefined,
    });
  };

  return (
    <>
      <Stack.Screen options={{ title: 'Sales summary' }} />
      <View style={styles.container}>
        <View style={styles.filters}>
          <Text style={styles.filterLabel}>Order from (YYYY-MM-DD)</Text>
          <TextInput
            value={orderFrom}
            onChangeText={setOrderFrom}
            placeholder="2026-01-01"
            autoCapitalize="none"
            style={styles.input}
          />
          <Text style={styles.filterLabel}>Order to (YYYY-MM-DD)</Text>
          <TextInput
            value={orderTo}
            onChangeText={setOrderTo}
            placeholder="2026-12-31"
            autoCapitalize="none"
            style={styles.input}
          />
          <Pressable onPress={applyFilters} style={styles.filterButton}>
            <Text style={styles.filterButtonText}>Apply filters</Text>
          </Pressable>
        </View>

        {query.isLoading ? (
          <View style={styles.centered}>
            <ActivityIndicator size="large" />
          </View>
        ) : query.isError ? (
          <View style={styles.centered}>
            <Text style={styles.error}>Could not load sales summary.</Text>
          </View>
        ) : report ? (
          <OptimizedFlatList
            data={report.by_status}
            keyExtractor={(item) => item.status}
            refreshControl={(
              <RefreshControl
                refreshing={query.isRefetching}
                onRefresh={() => {
                  void query.refetch();
                }}
              />
            )}
            ListHeaderComponent={(
              <View style={styles.summary}>
                <View style={styles.summaryCard}>
                  <Text style={styles.summaryLabel}>Orders</Text>
                  <Text style={styles.summaryValue}>{report.order_count}</Text>
                </View>
                <View style={styles.summaryCard}>
                  <Text style={styles.summaryLabel}>Total amount</Text>
                  <Text style={styles.summaryValue}>{report.total_amount}</Text>
                </View>
                <View style={styles.summaryCard}>
                  <Text style={styles.summaryLabel}>Payments received</Text>
                  <Text style={styles.summaryValue}>{report.payments_received}</Text>
                </View>
                <Text style={styles.sectionTitle}>By status</Text>
              </View>
            )}
            ListEmptyComponent={(
              <View style={styles.centered}>
                <Text style={styles.empty}>No status data.</Text>
              </View>
            )}
            renderItem={({ item }) => (
              <View style={styles.row}>
                <View style={styles.rowBody}>
                  <Text style={styles.name}>{item.status}</Text>
                  <Text style={styles.meta}>{item.order_count} orders</Text>
                </View>
                <Text style={styles.amount}>{item.total_amount}</Text>
              </View>
            )}
          />
        ) : null}
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f8fafc',
    flex: 1,
  },
  filters: {
    backgroundColor: '#fff',
    borderBottomColor: '#e2e8f0',
    borderBottomWidth: 1,
    padding: 16,
  },
  filterLabel: {
    color: '#334155',
    fontSize: 13,
    fontWeight: '600',
    marginBottom: 6,
    marginTop: 8,
  },
  input: {
    backgroundColor: '#f8fafc',
    borderColor: '#cbd5e1',
    borderRadius: 10,
    borderWidth: 1,
    fontSize: 16,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  filterButton: {
    alignItems: 'center',
    backgroundColor: '#2563eb',
    borderRadius: 10,
    marginTop: 12,
    paddingVertical: 12,
  },
  filterButtonText: {
    color: '#fff',
    fontSize: 15,
    fontWeight: '700',
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
  summary: {
    padding: 16,
    paddingBottom: 0,
  },
  summaryCard: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 12,
    padding: 16,
  },
  summaryLabel: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '600',
    textTransform: 'uppercase',
  },
  summaryValue: {
    color: '#0f172a',
    fontSize: 22,
    fontWeight: '700',
    marginTop: 8,
  },
  sectionTitle: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 8,
    marginTop: 4,
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
    textTransform: 'capitalize',
  },
  meta: {
    color: '#64748b',
    fontSize: 13,
    marginTop: 4,
  },
  amount: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '700',
  },
});
