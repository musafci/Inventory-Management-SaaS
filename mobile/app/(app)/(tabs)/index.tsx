import { ActivityIndicator, ScrollView, StyleSheet, Text, View } from 'react-native';
import { type Href } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { SymbolView } from 'expo-symbols';

import {
  Card,
  MetricTile,
  QuickAction,
  ScreenScrollView,
  SectionHeader,
  StatusBadge,
} from '@/components/ui';
import { NavPressable } from '@/components/ui/NavPressable';
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
import { gradients, palette, shadow, theme } from '@/src/theme';

function formatStatus(status: string): string {
  return status.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function orderStatusTone(status: string): 'default' | 'success' | 'warning' | 'danger' | 'info' {
  if (status.includes('cancel')) return 'danger';
  if (status.includes('deliver') || status.includes('received')) return 'success';
  if (status.includes('draft')) return 'default';
  if (status.includes('partial')) return 'warning';
  return 'info';
}

export default function HomeScreen() {
  const { user, organizationId, organizations, permissions } = useAuth();
  const organization = organizations.find((item) => item.id === organizationId);
  const { dashboardQuery, recentOrders, lowStockItems, isRefetching, refetch } = useDashboardHome();
  const stats = dashboardQuery.data;

  if (!canViewDashboard(permissions)) {
    return (
      <ScreenScrollView>
        <Text style={styles.heroTitle}>Dashboard</Text>
        <Text style={styles.heroSubtitle}>Welcome back, {user?.name ?? 'User'}</Text>
        <Card muted>
          <Text style={styles.cardLabel}>Access restricted</Text>
          <Text style={styles.cardMeta}>
            You do not have permission to view dashboard metrics.
          </Text>
        </Card>
      </ScreenScrollView>
    );
  }

  return (
    <ScreenScrollView refreshing={isRefetching} onRefresh={() => { void refetch(); }}>
      <LinearGradient
        colors={[...gradients.primaryHero]}
        end={{ x: 1, y: 1 }}
        start={{ x: 0, y: 0 }}
        style={[styles.hero, shadow('lg')]}>
        <Text style={styles.heroEyebrow}>Good to see you</Text>
        <Text style={styles.heroTitle}>{user?.name ?? 'User'}</Text>
        <Text style={styles.heroSubtitle}>{organization?.name ?? 'Your organization'}</Text>
        <View style={styles.heroMetaRow}>
          <View style={styles.heroPill}>
            <Text style={styles.heroPillText}>{organization?.plan ?? 'Plan'}</Text>
          </View>
          <View style={styles.heroPill}>
            <Text style={styles.heroPillText}>{organization?.status ?? 'Active'}</Text>
          </View>
        </View>
      </LinearGradient>

      <ScrollView
        horizontal
        contentContainerStyle={styles.quickActions}
        showsHorizontalScrollIndicator={false}>
        {canCreateInventory(permissions) ? (
          <QuickAction
            href="/(app)/products/new"
            icon={{ ios: 'plus.circle.fill', android: 'add_circle', web: 'add_circle' }}
            label="Add product"
            tone="sky"
          />
        ) : null}
        {canCreateSalesOrder(permissions) ? (
          <QuickAction
            href="/(app)/sales-orders/new"
            icon={{ ios: 'cart.fill', android: 'shopping_cart', web: 'shopping_cart' }}
            label="Sales order"
            tone="emerald"
          />
        ) : null}
        {canCreatePurchaseOrder(permissions) ? (
          <QuickAction
            href="/(app)/purchase-orders/new"
            icon={{ ios: 'shippingbox.fill', android: 'inventory_2', web: 'inventory_2' }}
            label="Purchase order"
            tone="amber"
          />
        ) : null}
        {canCreateInventory(permissions) ? (
          <QuickAction
            href="/(app)/stock-movements/new"
            icon={{ ios: 'arrow.left.arrow.right', android: 'sync_alt', web: 'sync_alt' }}
            label="Adjustment"
            tone="violet"
          />
        ) : null}
        {canViewReports(permissions) ? (
          <QuickAction
            href="/(app)/(tabs)/reports"
            icon={{ ios: 'chart.bar.fill', android: 'bar_chart', web: 'bar_chart' }}
            label="Reports"
            tone="sky"
          />
        ) : null}
      </ScrollView>

      {dashboardQuery.isLoading ? (
        <View style={styles.centered}>
          <ActivityIndicator color={theme.colors.primary} size="large" />
        </View>
      ) : dashboardQuery.isError ? (
        <Card>
          <Text style={styles.cardLabel}>Could not load metrics</Text>
          <Text style={styles.cardMeta}>Pull to refresh and try again.</Text>
        </Card>
      ) : stats ? (
        <View style={styles.metricsGrid}>
          <MetricTile
            icon={{ ios: 'cube.box.fill', android: 'inventory', web: 'inventory' }}
            label="Products"
            meta={`${stats.total_stock_items} stock items`}
            tone="sky"
            value={String(stats.total_products)}
          />
          <MetricTile
            icon={{ ios: 'dollarsign.circle.fill', android: 'paid', web: 'paid' }}
            label="Stock value"
            tone="emerald"
            value={stats.stock_value}
          />
          <MetricTile
            icon={{ ios: 'exclamationmark.triangle.fill', android: 'warning', web: 'warning' }}
            label="Low stock"
            meta="At or below reorder"
            tone="amber"
            value={String(stats.low_stock_count)}
          />
          <MetricTile
            icon={{ ios: 'doc.text.fill', android: 'description', web: 'description' }}
            label="Pending POs"
            tone="sky"
            value={String(stats.pending_purchase_orders)}
          />
          <MetricTile
            icon={{ ios: 'bag.fill', android: 'shopping_bag', web: 'shopping_bag' }}
            label="Pending SOs"
            tone="violet"
            value={String(stats.pending_sales_orders)}
          />
        </View>
      ) : null}

      <SectionHeader
        actionHref={canViewInventoryReports(permissions) ? '/(app)/reports/low-stock' : undefined}
        actionLabel={canViewInventoryReports(permissions) ? 'View all' : undefined}
        title="Low stock alerts"
      />
      {lowStockItems.length === 0 ? (
        <Card muted>
          <View style={styles.emptyInline}>
            <SymbolView
              name={{ ios: 'checkmark.seal.fill', android: 'verified', web: 'verified' }}
              size={22}
              tintColor={theme.colors.success}
            />
            <Text style={styles.cardMeta}>All stock levels look good.</Text>
          </View>
        </Card>
      ) : (
        lowStockItems.map((item) => (
          <View key={item.stock_id} style={[styles.alertRow, shadow('sm')]}>
            <View style={styles.alertIcon}>
              <SymbolView
                name={{ ios: 'exclamationmark.triangle.fill', android: 'warning', web: 'warning' }}
                size={18}
                tintColor={palette.amber500}
              />
            </View>
            <View style={styles.alertBody}>
              <Text style={styles.alertName}>{item.product_name}</Text>
              <Text style={styles.alertMeta}>
                {item.warehouse_name} · Reorder {item.reorder_point}
              </Text>
            </View>
            <Text style={styles.alertQty}>{item.quantity_available}</Text>
          </View>
        ))
      )}

      <SectionHeader title="Recent orders" />
      {recentOrders.length === 0 ? (
        <Card muted>
          <Text style={styles.cardMeta}>No recent orders yet.</Text>
        </Card>
      ) : (
        recentOrders.map((order) => {
          const href = (
            order.kind === 'purchase'
              ? `/(app)/purchase-orders/${order.id}`
              : `/(app)/sales-orders/${order.id}`
          ) as Href;

          return (
            <NavPressable key={`${order.kind}-${order.id}`} href={href} style={[styles.orderRow, shadow('sm')]}>
              <View style={[styles.orderIcon, order.kind === 'purchase' ? styles.poIcon : styles.soIcon]}>
                <SymbolView
                  name={
                    order.kind === 'purchase'
                      ? { ios: 'shippingbox.fill', android: 'inventory_2', web: 'inventory_2' }
                      : { ios: 'cart.fill', android: 'shopping_cart', web: 'shopping_cart' }
                  }
                  size={18}
                  tintColor={order.kind === 'purchase' ? palette.amber500 : palette.emerald500}
                />
              </View>
              <View style={styles.orderBody}>
                <Text style={styles.orderNumber}>
                  {order.kind === 'purchase' ? 'PO' : 'SO'} {order.number}
                </Text>
                <StatusBadge label={formatStatus(order.status)} tone={orderStatusTone(order.status)} />
              </View>
              <Text style={styles.orderAmount}>{order.total_amount}</Text>
            </NavPressable>
          );
        })
      )}
    </ScreenScrollView>
  );
}

const styles = StyleSheet.create({
  hero: {
    borderRadius: theme.radius.xl,
    marginBottom: theme.spacing.lg,
    padding: theme.spacing.xl,
  },
  heroEyebrow: {
    color: 'rgba(255,255,255,0.82)',
    fontSize: 13,
    fontWeight: '600',
    letterSpacing: 0.4,
    textTransform: 'uppercase',
  },
  heroTitle: {
    color: theme.colors.primaryText,
    fontSize: 30,
    fontWeight: '800',
    letterSpacing: -0.5,
    marginTop: theme.spacing.sm,
  },
  heroSubtitle: {
    color: 'rgba(255,255,255,0.92)',
    fontSize: 16,
    marginTop: 4,
  },
  heroMetaRow: {
    flexDirection: 'row',
    gap: theme.spacing.sm,
    marginTop: theme.spacing.lg,
  },
  heroPill: {
    backgroundColor: 'rgba(255,255,255,0.18)',
    borderRadius: theme.radius.pill,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  heroPillText: {
    color: theme.colors.primaryText,
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'capitalize',
  },
  quickActions: {
    gap: theme.spacing.sm,
    paddingBottom: theme.spacing.lg,
  },
  centered: {
    alignItems: 'center',
    paddingVertical: theme.spacing.xxxl,
  },
  metricsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: theme.spacing.md,
    marginBottom: theme.spacing.sm,
  },
  cardLabel: {
    ...theme.typography.label,
    color: theme.colors.textSecondary,
  },
  cardMeta: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    marginTop: theme.spacing.sm,
  },
  emptyInline: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: theme.spacing.sm,
  },
  alertRow: {
    alignItems: 'center',
    backgroundColor: palette.amber50,
    borderColor: palette.amber200,
    borderRadius: theme.radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    gap: theme.spacing.md,
    marginBottom: theme.spacing.sm,
    padding: theme.spacing.lg,
  },
  alertIcon: {
    alignItems: 'center',
    backgroundColor: theme.colors.surface,
    borderRadius: theme.radius.sm,
    height: 36,
    justifyContent: 'center',
    width: 36,
  },
  alertBody: {
    flex: 1,
  },
  alertName: {
    ...theme.typography.bodyStrong,
    color: theme.colors.text,
  },
  alertMeta: {
    ...theme.typography.caption,
    color: palette.amber700,
    marginTop: 4,
  },
  alertQty: {
    color: palette.amber700,
    fontSize: 18,
    fontWeight: '800',
  },
  orderRow: {
    alignItems: 'center',
    backgroundColor: theme.colors.surface,
    borderColor: theme.colors.border,
    borderRadius: theme.radius.md,
    borderWidth: StyleSheet.hairlineWidth,
    flexDirection: 'row',
    gap: theme.spacing.md,
    marginBottom: theme.spacing.sm,
    padding: theme.spacing.lg,
  },
  orderIcon: {
    alignItems: 'center',
    borderRadius: theme.radius.sm,
    height: 40,
    justifyContent: 'center',
    width: 40,
  },
  poIcon: {
    backgroundColor: palette.amber50,
  },
  soIcon: {
    backgroundColor: palette.emerald50,
  },
  orderBody: {
    flex: 1,
    gap: 6,
  },
  orderNumber: {
    ...theme.typography.bodyStrong,
    color: theme.colors.text,
  },
  orderAmount: {
    color: theme.colors.text,
    fontSize: 16,
    fontWeight: '800',
  },
});
