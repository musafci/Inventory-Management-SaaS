import { SymbolView } from 'expo-symbols';
import { Tabs, useRouter } from 'expo-router';
import { Platform, Pressable, StyleSheet, View } from 'react-native';

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
import { appIcon } from '@/src/theme/icons';

function SearchButton() {
  const router = useRouter();
  return (
    <Pressable
      accessibilityLabel="Search"
      onPress={() => router.push('/(app)/search')}
      style={styles.searchBtn}>
      <SymbolView
        name={appIcon({ ios: 'magnifyingglass', android: 'search', web: 'search' })}
        size={20}
        tintColor={theme.colors.textSecondary}
      />
    </Pressable>
  );
}

function TabIcon({ name, color, focused }: { name: { ios: string; android: string; web: string }; color: string; focused: boolean }) {
  return (
    <View style={styles.tabIconWrap}>
      <SymbolView
        name={focused ? appIcon({ ...name, ios: `${name.ios}.fill` }) : appIcon(name)}
        tintColor={color}
        size={24}
      />
      {focused ? <View style={styles.activeIndicator} /> : null}
    </View>
  );
}

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
            <SearchButton />
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
            <TabIcon name={{ ios: 'house', android: 'home', web: 'home' }} color={color as string} focused={focused} />
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
            <TabIcon name={{ ios: 'shippingbox', android: 'inventory', web: 'inventory' }} color={color as string} focused={focused} />
          ),
        }}
      />
      <Tabs.Screen
        name="sales"
        options={{
          title: 'Sales',
          href: canViewSales(permissions) ? undefined : null,
          tabBarIcon: ({ color, focused }) => (
            <TabIcon name={{ ios: 'cart', android: 'shopping_cart', web: 'shopping_cart' }} color={color as string} focused={focused} />
          ),
        }}
      />
      <Tabs.Screen
        name="purchasing"
        options={{
          title: 'Purchasing',
          href: canViewPurchasing(permissions) ? undefined : null,
          tabBarIcon: ({ color, focused }) => (
            <TabIcon name={{ ios: 'truck.box', android: 'local_shipping', web: 'local_shipping' }} color={color as string} focused={focused} />
          ),
        }}
      />
      <Tabs.Screen
        name="reports"
        options={{
          title: 'Reports',
          href: canViewReports(permissions) ? undefined : null,
          tabBarIcon: ({ color, focused }) => (
            <TabIcon name={{ ios: 'chart.bar', android: 'bar_chart', web: 'bar_chart' }} color={color as string} focused={focused} />
          ),
        }}
      />
      <Tabs.Screen
        name="more"
        options={{
          title: 'More',
          tabBarAccessibilityLabel: 'More tab',
          tabBarIcon: ({ color, focused }) => (
            <TabIcon name={{ ios: 'person.crop.circle', android: 'account_circle', web: 'account_circle' }} color={color as string} focused={focused} />
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
  tabIconWrap: {
    alignItems: 'center',
    gap: 4,
  },
  activeIndicator: {
    backgroundColor: theme.colors.primary,
    borderRadius: 2,
    height: 3,
    width: 20,
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
    alignItems: 'center',
    flexDirection: 'row',
    gap: theme.spacing.sm,
    marginRight: theme.spacing.md,
  },
  searchBtn: {
    alignItems: 'center',
    backgroundColor: theme.colors.surfaceMuted,
    borderColor: `${theme.colors.text}0D`,
    borderRadius: theme.radius.md,
    borderWidth: StyleSheet.hairlineWidth,
    height: 36,
    justifyContent: 'center',
    width: 36,
  },
});
