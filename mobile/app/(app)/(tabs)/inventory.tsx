import { type Href } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import { HubCard } from '@/components/HubCard';
import { useAuth } from '@/src/auth/AuthContext';
import { canCreateInventory } from '@/src/permissions';

type InventoryLink = {
  href: Href;
  title: string;
  body: string;
  testID: string;
  visible?: boolean;
};

export default function InventoryScreen() {
  const { permissions } = useAuth();

  const links: InventoryLink[] = [
    {
      href: '/(app)/products',
      title: 'Products',
      body: 'Browse, search, create, and edit products.',
      testID: 'hub-products',
    },
    {
      href: '/(app)/stocks',
      title: 'Stock levels',
      body: 'View on-hand and available quantities by warehouse.',
      testID: 'hub-stocks',
    },
    {
      href: '/(app)/stock-movements',
      title: 'Stock movements',
      body: 'Review ledger entries and record adjustments.',
      testID: 'hub-stock-movements',
    },
    {
      href: '/(app)/categories',
      title: 'Categories',
      body: 'Organize products into categories.',
      testID: 'hub-categories',
    },
    {
      href: '/(app)/units',
      title: 'Units',
      body: 'Manage measurement units for products.',
      testID: 'hub-units',
    },
    {
      href: '/(app)/imports/products' as Href,
      title: 'Import products (CSV)',
      body: 'Bulk upload products from a CSV file.',
      testID: 'hub-import-products',
      visible: canCreateInventory(permissions),
    },
  ];

  return (
    <View style={styles.container}>
      <Text accessibilityRole="header" style={styles.title}>Inventory</Text>
      <Text style={styles.description}>
        Manage catalog data and stock from the modules below.
      </Text>

      {links.filter((link) => link.visible !== false).map((link) => (
        <HubCard
          key={link.testID}
          href={link.href}
          title={link.title}
          body={link.body}
          testID={link.testID}
        />
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f8fafc',
    flex: 1,
    padding: 20,
  },
  title: {
    color: '#0f172a',
    fontSize: 28,
    fontWeight: '700',
  },
  description: {
    color: '#64748b',
    fontSize: 15,
    lineHeight: 22,
    marginBottom: 20,
    marginTop: 10,
  },
});
