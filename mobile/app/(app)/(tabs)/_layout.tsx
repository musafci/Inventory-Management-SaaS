import { SymbolView } from 'expo-symbols';
import { Tabs } from 'expo-router';
import { Platform, StyleSheet, View } from 'react-native';

import { OrgSwitcher } from '@/components/OrgSwitcher';
import { useClientOnlyValue } from '@/components/useClientOnlyValue';
import { useAuth } from '@/src/auth/AuthContext';
import {
  canViewDashboard,
  canViewInventory,
  canViewPurchasing,
  canViewReports,
  canViewSales,
} from '@/src/permissions';
import { theme } from '@/src/theme';

export default function TabLayout() {
  const { permissions, user } = useAuth();

  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor: theme.colors.primary,
        tabBarInactiveTintColor: theme.colors.textMuted,
        tabBarStyle: styles.tabBar,
        tabBarLabelStyle: styles.tabBarLabel,
        tabBarItemStyle: styles.tabBarItem,
        headerShown: useClientOnlyValue(false, true),
        headerStyle: styles.header,
        headerTitleStyle: styles.headerTitle,
        headerShadowVisible: false,
        headerRight: () => (
          <View style={styles.headerRight}>
            <OrgSwitcher />
          </View>
        ),
      }}>
      <Tabs.Screen
        name="index"
        options={{
          title: 'Home',
          href: canViewDashboard(permissions) ? undefined : null,
          tabBarAccessibilityLabel: 'Home tab',
          tabBarIcon: ({ color, focused }) => (
            <SymbolView
              name={{ ios: focused ? 'house.fill' : 'house', android: 'home', web: 'home' }}
              tintColor={color}
              size={24}
            />
          ),
        }}
      />
      <Tabs.Screen
        name="inventory"
        options={{
          title: 'Inventory',
          href: canViewInventory(permissions) ? undefined : null,
          tabBarAccessibilityLabel: 'Inventory tab',
          tabBarIcon: ({ color, focused }) => (
            <SymbolView
              name={{ ios: focused ? 'shippingbox.fill' : 'shippingbox', android: 'inventory', web: 'inventory' }}
              tintColor={color}
              size={24}
            />
          ),
        }}
      />
      <Tabs.Screen
        name="sales"
        options={{
          title: 'Sales',
          href: canViewSales(permissions) ? undefined : null,
          tabBarIcon: ({ color, focused }) => (
            <SymbolView
              name={{ ios: focused ? 'cart.fill' : 'cart', android: 'shopping_cart', web: 'shopping_cart' }}
              tintColor={color}
              size={24}
            />
          ),
        }}
      />
      <Tabs.Screen
        name="purchasing"
        options={{
          title: 'Purchasing',
          href: canViewPurchasing(permissions) ? undefined : null,
          tabBarIcon: ({ color, focused }) => (
            <SymbolView
              name={{ ios: focused ? 'truck.box.fill' : 'truck.box', android: 'local_shipping', web: 'local_shipping' }}
              tintColor={color}
              size={24}
            />
          ),
        }}
      />
      <Tabs.Screen
        name="reports"
        options={{
          title: 'Reports',
          href: canViewReports(permissions) ? undefined : null,
          tabBarIcon: ({ color, focused }) => (
            <SymbolView
              name={{ ios: focused ? 'chart.bar.fill' : 'chart.bar', android: 'bar_chart', web: 'bar_chart' }}
              tintColor={color}
              size={24}
            />
          ),
        }}
      />
      <Tabs.Screen
        name="more"
        options={{
          title: 'More',
          tabBarAccessibilityLabel: 'More tab',
          tabBarIcon: ({ color, focused }) => (
            <SymbolView
              name={{ ios: focused ? 'person.crop.circle.fill' : 'person.crop.circle', android: 'account_circle', web: 'account_circle' }}
              tintColor={color}
              size={24}
            />
          ),
          headerTitle: user?.name ?? 'Account',
        }}
      />
    </Tabs>
  );
}

const styles = StyleSheet.create({
  tabBar: {
    backgroundColor: theme.colors.surface,
    borderTopColor: theme.colors.border,
    borderTopWidth: StyleSheet.hairlineWidth,
    height: Platform.OS === 'ios' ? 88 : 68,
    paddingBottom: Platform.OS === 'ios' ? 24 : 10,
    paddingTop: 8,
  },
  tabBarLabel: {
    fontSize: 11,
    fontWeight: '700',
  },
  tabBarItem: {
    paddingTop: 2,
  },
  header: {
    backgroundColor: theme.colors.background,
  },
  headerTitle: {
    color: theme.colors.text,
    fontSize: 18,
    fontWeight: '800',
  },
  headerRight: {
    marginRight: theme.spacing.md,
  },
});
