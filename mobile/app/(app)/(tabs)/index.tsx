import {
  ActivityIndicator,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { useAuth } from '@/src/auth/AuthContext';
import { useDashboard } from '@/src/hooks/useReports';
import { canViewDashboard } from '@/src/permissions';

function MetricCard({
  label,
  value,
  meta,
}: {
  label: string;
  value: string;
  meta?: string;
}) {
  return (
    <View style={styles.metricCard}>
      <Text style={styles.metricLabel}>{label}</Text>
      <Text style={styles.metricValue}>{value}</Text>
      {meta ? <Text style={styles.metricMeta}>{meta}</Text> : null}
    </View>
  );
}

export default function HomeScreen() {
  const { user, organizationId, organizations, permissions } = useAuth();
  const organization = organizations.find((item) => item.id === organizationId);
  const query = useDashboard();
  const stats = query.data;

  if (!canViewDashboard(permissions)) {
    return (
      <View style={styles.container}>
        <Text style={styles.title}>Dashboard</Text>
        <Text style={styles.subtitle}>Welcome back, {user?.name ?? 'User'}</Text>
        <View style={styles.card}>
          <Text style={styles.cardLabel}>Access restricted</Text>
          <Text style={styles.cardMeta}>
            You do not have permission to view dashboard metrics.
          </Text>
        </View>
      </View>
    );
  }

  return (
    <ScrollView
      contentContainerStyle={styles.container}
      refreshControl={(
        <RefreshControl
          refreshing={query.isRefetching}
          onRefresh={() => {
            void query.refetch();
          }}
        />
      )}>
      <Text style={styles.title}>Dashboard</Text>
      <Text style={styles.subtitle}>Welcome back, {user?.name ?? 'User'}</Text>

      <View style={styles.card}>
        <Text style={styles.cardLabel}>Organization</Text>
        <Text style={styles.cardValue}>{organization?.name ?? '—'}</Text>
        <Text style={styles.cardMeta}>
          {organization?.plan ?? '—'} · {organization?.status ?? '—'}
        </Text>
      </View>

      {query.isLoading ? (
        <View style={styles.centered}>
          <ActivityIndicator size="large" />
        </View>
      ) : query.isError ? (
        <View style={styles.card}>
          <Text style={styles.cardLabel}>Error</Text>
          <Text style={styles.cardMeta}>Could not load dashboard metrics.</Text>
        </View>
      ) : stats ? (
        <View style={styles.metricsGrid}>
          <MetricCard
            label="Products"
            value={String(stats.total_products)}
            meta={`${stats.total_stock_items} stock items`}
          />
          <MetricCard
            label="Stock value"
            value={stats.stock_value}
          />
          <MetricCard
            label="Low stock"
            value={String(stats.low_stock_count)}
            meta="Items at or below reorder point"
          />
          <MetricCard
            label="Pending POs"
            value={String(stats.pending_purchase_orders)}
          />
          <MetricCard
            label="Pending SOs"
            value={String(stats.pending_sales_orders)}
          />
        </View>
      ) : null}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f8fafc',
    flexGrow: 1,
    padding: 20,
  },
  title: {
    color: '#0f172a',
    fontSize: 28,
    fontWeight: '700',
  },
  subtitle: {
    color: '#64748b',
    fontSize: 15,
    marginBottom: 20,
    marginTop: 6,
  },
  card: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 16,
    borderWidth: 1,
    marginBottom: 12,
    padding: 16,
  },
  cardLabel: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '600',
    textTransform: 'uppercase',
  },
  cardValue: {
    color: '#0f172a',
    fontSize: 20,
    fontWeight: '700',
    marginTop: 8,
  },
  cardMeta: {
    color: '#64748b',
    fontSize: 13,
    marginTop: 6,
  },
  centered: {
    alignItems: 'center',
    paddingVertical: 32,
  },
  metricsGrid: {
    gap: 12,
  },
  metricCard: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 16,
    borderWidth: 1,
    padding: 16,
  },
  metricLabel: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '600',
    textTransform: 'uppercase',
  },
  metricValue: {
    color: '#0f172a',
    fontSize: 24,
    fontWeight: '700',
    marginTop: 8,
  },
  metricMeta: {
    color: '#64748b',
    fontSize: 13,
    marginTop: 6,
  },
});
