import { Link } from 'expo-router';
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { useAuth } from '@/src/auth/AuthContext';
import { useDashboardHome } from '@/src/hooks/useDashboardHome';
import {
  canCreateInventory,
  canCreatePurchaseOrder,
  canCreateSalesOrder,
  canViewDashboard,
  canViewInventoryReports,
  canViewReports,
} from '@/src/permissions';

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

function formatStatus(status: string): string {
  return status.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

export default function HomeScreen() {
  const { user, organizationId, organizations, permissions } = useAuth();
  const organization = organizations.find((item) => item.id === organizationId);
  const { dashboardQuery, recentOrders, lowStockItems, isRefetching, refetch } = useDashboardHome();
  const stats = dashboardQuery.data;

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
          refreshing={isRefetching}
          onRefresh={() => {
            void refetch();
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

      <View style={styles.quickActions}>
        {canCreateInventory(permissions) ? (
          <Link href="/(app)/products/new" asChild>
            <Pressable style={styles.quickAction}>
              <Text style={styles.quickActionText}>Add product</Text>
            </Pressable>
          </Link>
        ) : null}
        {canViewReports(permissions) ? (
          <Link href="/(app)/(tabs)/reports" asChild>
            <Pressable style={styles.quickAction}>
              <Text style={styles.quickActionText}>View reports</Text>
            </Pressable>
          </Link>
        ) : null}
        {canCreatePurchaseOrder(permissions) ? (
          <Link href="/(app)/purchase-orders/new" asChild>
            <Pressable style={styles.quickAction}>
              <Text style={styles.quickActionText}>Purchase order</Text>
            </Pressable>
          </Link>
        ) : null}
        {canCreateSalesOrder(permissions) ? (
          <Link href="/(app)/sales-orders/new" asChild>
            <Pressable style={styles.quickAction}>
              <Text style={styles.quickActionText}>Sales order</Text>
            </Pressable>
          </Link>
        ) : null}
        {canCreateInventory(permissions) ? (
          <Link href="/(app)/stock-movements/new" asChild>
            <Pressable style={styles.quickAction}>
              <Text style={styles.quickActionText}>Stock adjustment</Text>
            </Pressable>
          </Link>
        ) : null}
      </View>

      {dashboardQuery.isLoading ? (
        <View style={styles.centered}>
          <ActivityIndicator size="large" />
        </View>
      ) : dashboardQuery.isError ? (
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
          <MetricCard label="Stock value" value={stats.stock_value} />
          <MetricCard
            label="Low stock"
            value={String(stats.low_stock_count)}
            meta="Items at or below reorder point"
          />
          <MetricCard label="Pending POs" value={String(stats.pending_purchase_orders)} />
          <MetricCard label="Pending SOs" value={String(stats.pending_sales_orders)} />
        </View>
      ) : null}

      <View style={styles.section}>
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Low stock alerts</Text>
          {canViewInventoryReports(permissions) ? (
            <Link href="/(app)/reports/low-stock" style={styles.sectionLink}>
              View all
            </Link>
          ) : null}
        </View>
        {lowStockItems.length === 0 ? (
          <View style={styles.card}>
            <Text style={styles.cardMeta}>All stock levels look good.</Text>
          </View>
        ) : (
          lowStockItems.map((item) => (
            <View key={item.stock_id} style={styles.alertRow}>
              <View style={styles.alertBody}>
                <Text style={styles.alertName}>{item.product_name}</Text>
                <Text style={styles.alertMeta}>
                  {item.warehouse_name} · Reorder {item.reorder_point}
                </Text>
              </View>
              <Text style={styles.alertQty}>{item.quantity_available} left</Text>
            </View>
          ))
        )}
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Recent orders</Text>
        {recentOrders.length === 0 ? (
          <View style={styles.card}>
            <Text style={styles.cardMeta}>No recent orders yet.</Text>
          </View>
        ) : (
          recentOrders.map((order) => (
            <Link
              key={`${order.kind}-${order.id}`}
              href={
                order.kind === 'purchase'
                  ? `/(app)/purchase-orders/${order.id}`
                  : `/(app)/sales-orders/${order.id}`
              }
              asChild>
              <Pressable style={styles.orderRow}>
                <View style={styles.orderBody}>
                  <Text style={styles.orderNumber}>
                    {order.kind === 'purchase' ? 'PO' : 'SO'} {order.number}
                  </Text>
                  <Text style={styles.orderMeta}>{formatStatus(order.status)}</Text>
                </View>
                <Text style={styles.orderAmount}>{order.total_amount}</Text>
              </Pressable>
            </Link>
          ))
        )}
      </View>
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
  quickActions: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 16,
  },
  quickAction: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 10,
    borderWidth: 1,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  quickActionText: {
    color: '#2563eb',
    fontSize: 14,
    fontWeight: '600',
  },
  centered: {
    alignItems: 'center',
    paddingVertical: 32,
  },
  metricsGrid: {
    gap: 12,
    marginBottom: 8,
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
  section: {
    marginTop: 16,
  },
  sectionHeader: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 10,
  },
  sectionTitle: {
    color: '#0f172a',
    fontSize: 18,
    fontWeight: '700',
  },
  sectionLink: {
    color: '#2563eb',
    fontSize: 14,
    fontWeight: '600',
  },
  alertRow: {
    alignItems: 'center',
    backgroundColor: '#fffbeb',
    borderColor: '#fde68a',
    borderRadius: 12,
    borderWidth: 1,
    flexDirection: 'row',
    marginBottom: 8,
    padding: 14,
  },
  alertBody: {
    flex: 1,
  },
  alertName: {
    color: '#0f172a',
    fontSize: 15,
    fontWeight: '600',
  },
  alertMeta: {
    color: '#92400e',
    fontSize: 13,
    marginTop: 4,
  },
  alertQty: {
    color: '#b45309',
    fontSize: 14,
    fontWeight: '700',
  },
  orderRow: {
    alignItems: 'center',
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 12,
    borderWidth: 1,
    flexDirection: 'row',
    marginBottom: 8,
    padding: 14,
  },
  orderBody: {
    flex: 1,
  },
  orderNumber: {
    color: '#0f172a',
    fontSize: 15,
    fontWeight: '600',
  },
  orderMeta: {
    color: '#64748b',
    fontSize: 13,
    marginTop: 4,
  },
  orderAmount: {
    color: '#0f172a',
    fontSize: 15,
    fontWeight: '700',
  },
});
