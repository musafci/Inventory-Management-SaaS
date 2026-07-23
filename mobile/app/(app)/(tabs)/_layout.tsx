import { SymbolView } from 'expo-symbols';
import { Tabs } from 'expo-router';
import { View } from 'react-native';

import { OrgSwitcher } from '@/components/OrgSwitcher';
import Colors from '@/constants/Colors';
import { useColorScheme } from '@/components/useColorScheme';
import { useClientOnlyValue } from '@/components/useClientOnlyValue';
import { useAuth } from '@/src/auth/AuthContext';
import {
  canViewDashboard,
  canViewInventory,
  canViewPurchasing,
  canViewReports,
  canViewSales,
} from '@/src/permissions';

export default function TabLayout() {
  const colorScheme = useColorScheme();
  const { permissions, user } = useAuth();

  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor: Colors[colorScheme ?? 'light'].tint,
        headerShown: useClientOnlyValue(false, true),
        headerRight: () => (
          <View style={{ marginRight: 12 }}>
            <OrgSwitcher />
          </View>
        ),
      }}>
      <Tabs.Screen
        name="index"
        options={{
          title: 'Home',
          href: canViewDashboard(permissions) ? undefined : null,
          tabBarIcon: ({ color }) => (
            <SymbolView name={{ ios: 'house.fill', android: 'home', web: 'home' }} tintColor={color} size={24} />
          ),
        }}
      />
      <Tabs.Screen
        name="inventory"
        options={{
          title: 'Inventory',
          href: canViewInventory(permissions) ? undefined : null,
          tabBarIcon: ({ color }) => (
            <SymbolView name={{ ios: 'shippingbox.fill', android: 'inventory', web: 'inventory' }} tintColor={color} size={24} />
          ),
        }}
      />
      <Tabs.Screen
        name="sales"
        options={{
          title: 'Sales',
          href: canViewSales(permissions) ? undefined : null,
          tabBarIcon: ({ color }) => (
            <SymbolView name={{ ios: 'cart.fill', android: 'shopping_cart', web: 'shopping_cart' }} tintColor={color} size={24} />
          ),
        }}
      />
      <Tabs.Screen
        name="purchasing"
        options={{
          title: 'Purchasing',
          href: canViewPurchasing(permissions) ? undefined : null,
          tabBarIcon: ({ color }) => (
            <SymbolView name={{ ios: 'truck.box.fill', android: 'local_shipping', web: 'local_shipping' }} tintColor={color} size={24} />
          ),
        }}
      />
      <Tabs.Screen
        name="reports"
        options={{
          title: 'Reports',
          href: canViewReports(permissions) ? undefined : null,
          tabBarIcon: ({ color }) => (
            <SymbolView name={{ ios: 'chart.bar.fill', android: 'bar_chart', web: 'bar_chart' }} tintColor={color} size={24} />
          ),
        }}
      />
      <Tabs.Screen
        name="more"
        options={{
          title: 'More',
          tabBarIcon: ({ color }) => (
            <SymbolView name={{ ios: 'ellipsis.circle.fill', android: 'more_horiz', web: 'more_horiz' }} tintColor={color} size={24} />
          ),
          headerTitle: user?.name ?? 'Account',
        }}
      />
    </Tabs>
  );
}
