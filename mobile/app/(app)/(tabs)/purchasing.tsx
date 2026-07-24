import { type Href } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import { HubCard } from '@/components/HubCard';
import { useAuth } from '@/src/auth/AuthContext';
import { canCreateSupplier } from '@/src/permissions';

type PurchasingLink = {
  href: Href;
  title: string;
  body: string;
  testID: string;
  visible?: boolean;
};

export default function PurchasingScreen() {
  const { permissions } = useAuth();

  const links: PurchasingLink[] = [
    {
      href: '/(app)/suppliers',
      title: 'Suppliers',
      body: 'Browse, create, edit, and delete suppliers.',
      testID: 'hub-suppliers',
    },
    {
      href: '/(app)/purchase-orders',
      title: 'Purchase orders',
      body: 'Create orders, receive stock, and record payments.',
      testID: 'hub-purchase-orders',
    },
    {
      href: '/(app)/imports/suppliers' as Href,
      title: 'Import suppliers (CSV)',
      body: 'Bulk upload suppliers from a CSV file.',
      testID: 'hub-import-suppliers',
      visible: canCreateSupplier(permissions),
    },
  ];

  return (
    <View style={styles.container}>
      <Text accessibilityRole="header" style={styles.title}>Purchasing</Text>
      <Text style={styles.description}>
        Manage suppliers and purchase orders from the modules below.
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
