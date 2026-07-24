import { Redirect, Stack } from 'expo-router';
import { ActivityIndicator, StyleSheet, View } from 'react-native';

import { ImpersonationBanner } from '@/components/ImpersonationBanner';
import { OfflineBanner } from '@/components/OfflineBanner';
import { useAuth } from '@/src/auth/AuthContext';
import { useNotificationNavigation } from '@/src/notifications/handler';
import { theme } from '@/src/theme';

const stackScreenOptions = {
  headerStyle: { backgroundColor: theme.colors.background },
  headerTitleStyle: {
    color: theme.colors.text,
    fontSize: 18,
    fontWeight: '800' as const,
  },
  headerTintColor: theme.colors.primary,
  headerShadowVisible: false,
  contentStyle: { backgroundColor: theme.colors.background },
};

export default function AppLayout() {
  const { isAuthenticated, isLoading } = useAuth();
  useNotificationNavigation();

  if (isLoading) {
    return (
      <View style={styles.loading}>
        <ActivityIndicator color={theme.colors.primary} size="large" />
      </View>
    );
  }

  if (!isAuthenticated) {
    return <Redirect href="/(auth)/login" />;
  }

  return (
    <>
      <ImpersonationBanner />
      <OfflineBanner />
      <Stack screenOptions={stackScreenOptions}>
        <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
        <Stack.Screen name="search" options={{ title: 'Search' }} />
        <Stack.Screen name="products/index" options={{ title: 'Products' }} />
        <Stack.Screen name="products/new" options={{ title: 'New product' }} />
        <Stack.Screen name="products/[id]" options={{ title: 'Product' }} />
        <Stack.Screen name="products/[id]/edit" options={{ title: 'Edit product' }} />
        <Stack.Screen name="stocks/index" options={{ title: 'Stock levels' }} />
        <Stack.Screen name="stock-movements/index" options={{ title: 'Stock movements' }} />
        <Stack.Screen name="stock-movements/new" options={{ title: 'Record adjustment' }} />
        <Stack.Screen name="categories/index" options={{ title: 'Categories' }} />
        <Stack.Screen name="categories/new" options={{ title: 'New category' }} />
        <Stack.Screen name="categories/[id]/edit" options={{ title: 'Edit category' }} />
        <Stack.Screen name="units/index" options={{ title: 'Units' }} />
        <Stack.Screen name="units/new" options={{ title: 'New unit' }} />
        <Stack.Screen name="units/[id]/edit" options={{ title: 'Edit unit' }} />
        <Stack.Screen name="warehouses/index" options={{ title: 'Warehouses' }} />
        <Stack.Screen name="warehouses/new" options={{ title: 'New warehouse' }} />
        <Stack.Screen name="warehouses/[id]/edit" options={{ title: 'Edit warehouse' }} />
        <Stack.Screen name="suppliers/index" options={{ title: 'Suppliers' }} />
        <Stack.Screen name="suppliers/new" options={{ title: 'New supplier' }} />
        <Stack.Screen name="suppliers/[id]/edit" options={{ title: 'Edit supplier' }} />
        <Stack.Screen name="customers/index" options={{ title: 'Customers' }} />
        <Stack.Screen name="customers/new" options={{ title: 'New customer' }} />
        <Stack.Screen name="customers/[id]/edit" options={{ title: 'Edit customer' }} />
        <Stack.Screen name="purchase-orders/index" options={{ title: 'Purchase orders' }} />
        <Stack.Screen name="purchase-orders/new" options={{ title: 'New purchase order' }} />
        <Stack.Screen name="purchase-orders/[id]" options={{ title: 'Purchase order' }} />
        <Stack.Screen name="purchase-orders/[id]/edit" options={{ title: 'Edit purchase order' }} />
        <Stack.Screen name="sales-orders/index" options={{ title: 'Sales orders' }} />
        <Stack.Screen name="sales-orders/new" options={{ title: 'New sales order' }} />
        <Stack.Screen name="sales-orders/[id]" options={{ title: 'Sales order' }} />
        <Stack.Screen name="sales-orders/[id]/edit" options={{ title: 'Edit sales order' }} />
        <Stack.Screen name="payments/index" options={{ title: 'Payments' }} />
        <Stack.Screen name="payments/[id]" options={{ title: 'Payment' }} />
        <Stack.Screen name="imports/products" options={{ title: 'Import products' }} />
        <Stack.Screen name="imports/customers" options={{ title: 'Import customers' }} />
        <Stack.Screen name="imports/suppliers" options={{ title: 'Import suppliers' }} />
        <Stack.Screen name="reports/stock-valuation" options={{ title: 'Stock valuation' }} />
        <Stack.Screen name="reports/low-stock" options={{ title: 'Low stock' }} />
        <Stack.Screen name="reports/sales-summary" options={{ title: 'Sales summary' }} />
        <Stack.Screen name="reports/purchase-summary" options={{ title: 'Purchase summary' }} />
        <Stack.Screen name="reports/exports" options={{ title: 'Report exports' }} />
        <Stack.Screen name="settings/index" options={{ title: 'Settings' }} />
        <Stack.Screen name="settings/organization" options={{ title: 'Organization' }} />
        <Stack.Screen name="settings/billing" options={{ title: 'Billing' }} />
        <Stack.Screen name="settings/team/index" options={{ title: 'Team' }} />
        <Stack.Screen name="settings/team/new" options={{ title: 'Add team member' }} />
        <Stack.Screen name="settings/team/[id]/edit" options={{ title: 'Edit team member' }} />
        <Stack.Screen name="settings/roles/index" options={{ title: 'Roles' }} />
        <Stack.Screen name="settings/roles/new" options={{ title: 'New role' }} />
        <Stack.Screen name="settings/roles/[id]/edit" options={{ title: 'Edit role' }} />
        <Stack.Screen name="settings/privacy" options={{ title: 'Privacy & data' }} />
        <Stack.Screen name="settings/notifications" options={{ title: 'Notifications' }} />
        <Stack.Screen name="settings/sync" options={{ title: 'Sync status' }} />
        <Stack.Screen name="settings/sessions" options={{ title: 'Active sessions' }} />
      </Stack>
    </>
  );
}

const styles = StyleSheet.create({
  loading: {
    alignItems: 'center',
    backgroundColor: theme.colors.background,
    flex: 1,
    justifyContent: 'center',
  },
});
