import { Link, type Href } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useAuth } from '@/src/auth/AuthContext';
import { canCreateSupplier } from '@/src/permissions';

export default function PurchasingScreen() {
  const { permissions } = useAuth();

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Purchasing</Text>
      <Text style={styles.description}>
        Manage suppliers and purchase orders from the modules below.
      </Text>

      <Link href="/(app)/suppliers" asChild>
        <Pressable style={styles.card}>
          <Text style={styles.cardTitle}>Suppliers</Text>
          <Text style={styles.cardBody}>Browse, create, edit, and delete suppliers.</Text>
        </Pressable>
      </Link>

      <Link href="/(app)/purchase-orders" asChild>
        <Pressable style={styles.card}>
          <Text style={styles.cardTitle}>Purchase orders</Text>
          <Text style={styles.cardBody}>Create orders, receive stock, and record payments.</Text>
        </Pressable>
      </Link>

      {canCreateSupplier(permissions) ? (
        <Link href={'/(app)/imports/suppliers' as Href} asChild>
          <Pressable style={styles.card}>
            <Text style={styles.cardTitle}>Import suppliers (CSV)</Text>
            <Text style={styles.cardBody}>Bulk upload suppliers from a CSV file.</Text>
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
