import { Stack } from 'expo-router';
import {
  ActivityIndicator,
  FlatList,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { useStockValuation } from '@/src/hooks/useReports';

export default function StockValuationScreen() {
  const query = useStockValuation();
  const report = query.data;

  return (
    <>
      <Stack.Screen options={{ title: 'Stock valuation' }} />
      <View style={styles.container}>
        {query.isLoading ? (
          <View style={styles.centered}>
            <ActivityIndicator size="large" />
          </View>
        ) : query.isError ? (
          <View style={styles.centered}>
            <Text style={styles.error}>Could not load stock valuation report.</Text>
          </View>
        ) : report ? (
          <FlatList
            data={report.by_warehouse}
            keyExtractor={(item) => String(item.warehouse_id)}
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
                  <Text style={styles.summaryLabel}>Total value</Text>
                  <Text style={styles.summaryValue}>{report.total_value}</Text>
                </View>
                <View style={styles.summaryCard}>
                  <Text style={styles.summaryLabel}>Total units</Text>
                  <Text style={styles.summaryValue}>{report.total_units}</Text>
                </View>
                <Text style={styles.sectionTitle}>By warehouse</Text>
              </View>
            )}
            ListEmptyComponent={(
              <View style={styles.centered}>
                <Text style={styles.empty}>No warehouse data.</Text>
              </View>
            )}
            renderItem={({ item }) => (
              <View style={styles.row}>
                <View style={styles.rowBody}>
                  <Text style={styles.name}>{item.warehouse_name}</Text>
                  <Text style={styles.meta}>{item.total_units} units</Text>
                </View>
                <Text style={styles.amount}>{item.total_value}</Text>
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
    fontSize: 24,
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
