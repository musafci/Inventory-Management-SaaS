import { Link, type Href } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useAuth } from '@/src/auth/AuthContext';
import { canCreateCustomer } from '@/src/permissions';

export default function SalesScreen() {
  const { permissions } = useAuth();

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Sales</Text>
      <Text style={styles.description}>
        Manage customers, sales orders, and payments from the modules below.
      </Text>

      <Link href="/(app)/customers" asChild>
        <Pressable style={styles.card}>
          <Text style={styles.cardTitle}>Customers</Text>
          <Text style={styles.cardBody}>Browse, create, edit, and delete customers.</Text>
        </Pressable>
      </Link>

      <Link href="/(app)/sales-orders" asChild>
        <Pressable style={styles.card}>
          <Text style={styles.cardTitle}>Sales orders</Text>
          <Text style={styles.cardBody}>Create orders, fulfill shipments, and collect payments.</Text>
        </Pressable>
      </Link>

      <Link href="/(app)/payments" asChild>
        <Pressable style={styles.card}>
          <Text style={styles.cardTitle}>Payments</Text>
          <Text style={styles.cardBody}>Review payment history and transaction details.</Text>
        </Pressable>
      </Link>

      {canCreateCustomer(permissions) ? (
        <Link href={'/(app)/imports/customers' as Href} asChild>
          <Pressable style={styles.card}>
            <Text style={styles.cardTitle}>Import customers (CSV)</Text>
            <Text style={styles.cardBody}>Bulk upload customers from a CSV file.</Text>
          </Pressable>
        </Link>
      ) : null}
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
  card: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 12,
    padding: 16,
  },
  cardTitle: {
    color: '#0f172a',
    fontSize: 17,
    fontWeight: '700',
  },
  cardBody: {
    color: '#64748b',
    fontSize: 14,
    lineHeight: 20,
    marginTop: 6,
  },
});
