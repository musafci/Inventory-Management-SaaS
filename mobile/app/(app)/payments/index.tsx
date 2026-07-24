import { Link, Stack } from 'expo-router';
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { OptimizedFlatList } from '@/components/OptimizedFlatList';

import { usePayments, usePaymentsList } from '@/src/hooks/usePayments';

function formatPayableType(payableType: string): string {
  if (payableType.includes('PurchaseOrder')) {
    return 'Purchase order';
  }

  if (payableType.includes('SalesOrder')) {
    return 'Sales order';
  }

  return payableType.split('\\').pop() ?? payableType;
}

function formatStatus(status: string): string {
  return status.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

export default function PaymentsScreen() {
  const query = usePayments();
  const payments = usePaymentsList();

  return (
    <>
      <Stack.Screen options={{ title: 'Payments' }} />

      <View style={styles.container}>
        {query.isLoading ? (
          <View style={styles.centered}>
            <ActivityIndicator size="large" />
          </View>
        ) : (
          <OptimizedFlatList
            data={payments}
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
                <Text style={styles.empty}>No payments yet.</Text>
              </View>
            )}
            ListFooterComponent={
              query.isFetchingNextPage ? (
                <ActivityIndicator style={styles.footerLoader} />
              ) : null
            }
            renderItem={({ item }) => (
              <Link href={`/(app)/payments/${item.id}`} asChild>
                <Pressable style={styles.row}>
                  <View style={styles.rowBody}>
                    <Text style={styles.name}>{item.amount}</Text>
                    <Text style={styles.meta}>
                      {formatStatus(String(item.method))} · {formatStatus(item.status)}
                    </Text>
                    <Text style={styles.meta}>
                      {formatPayableType(item.payable_type)} #{item.payable_id}
                    </Text>
                  </View>
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
  centered: {
    alignItems: 'center',
    justifyContent: 'center',
    padding: 32,
  },
  empty: {
    color: '#64748b',
    fontSize: 15,
  },
  row: {
    backgroundColor: '#fff',
    borderBottomColor: '#e2e8f0',
    borderBottomWidth: 1,
    paddingHorizontal: 16,
    paddingVertical: 14,
  },
  rowBody: {
    flex: 1,
  },
  name: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '700',
  },
  meta: {
    color: '#64748b',
    fontSize: 13,
    marginTop: 4,
  },
  footerLoader: {
    marginVertical: 16,
  },
});
