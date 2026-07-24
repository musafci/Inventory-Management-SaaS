import { type Href } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import { HubCard } from '@/components/HubCard';
import { useAuth } from '@/src/auth/AuthContext';
import { canCreateCustomer } from '@/src/permissions';

type SalesLink = {
  href: Href;
  title: string;
  body: string;
  testID: string;
  visible?: boolean;
};

export default function SalesScreen() {
  const { permissions } = useAuth();

  const links: SalesLink[] = [
    {
      href: '/(app)/customers',
      title: 'Customers',
      body: 'Browse, create, edit, and delete customers.',
      testID: 'hub-customers',
    },
    {
      href: '/(app)/sales-orders',
      title: 'Sales orders',
      body: 'Create orders, fulfill shipments, and collect payments.',
      testID: 'hub-sales-orders',
    },
    {
      href: '/(app)/payments',
      title: 'Payments',
      body: 'Review payment history and transaction details.',
      testID: 'hub-payments',
    },
    {
      href: '/(app)/imports/customers' as Href,
      title: 'Import customers (CSV)',
      body: 'Bulk upload customers from a CSV file.',
      testID: 'hub-import-customers',
      visible: canCreateCustomer(permissions),
    },
  ];

  return (
    <View style={styles.container}>
      <Text accessibilityRole="header" style={styles.title}>Sales</Text>
      <Text style={styles.description}>
        Manage customers, sales orders, and payments from the modules below.
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
