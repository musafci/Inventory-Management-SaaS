import { Link } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

export default function InventoryScreen() {
  return (
    <View style={styles.container}>
      <Text style={styles.title}>Inventory</Text>
      <Text style={styles.description}>
        Manage catalog data and stock from the modules below.
      </Text>

      <Link href="/(app)/products" asChild>
        <Pressable style={styles.card}>
          <Text style={styles.cardTitle}>Products</Text>
          <Text style={styles.cardBody}>Browse, search, create, and edit products.</Text>
        </Pressable>
      </Link>

      <Link href="/(app)/stocks" asChild>
        <Pressable style={styles.card}>
          <Text style={styles.cardTitle}>Stock levels</Text>
          <Text style={styles.cardBody}>View on-hand and available quantities by warehouse.</Text>
        </Pressable>
      </Link>

      <Link href="/(app)/stock-movements" asChild>
        <Pressable style={styles.card}>
          <Text style={styles.cardTitle}>Stock movements</Text>
          <Text style={styles.cardBody}>Review ledger entries and record adjustments.</Text>
        </Pressable>
      </Link>

      <Link href="/(app)/categories" asChild>
        <Pressable style={styles.card}>
          <Text style={styles.cardTitle}>Categories</Text>
          <Text style={styles.cardBody}>Organize products into categories.</Text>
        </Pressable>
      </Link>

      <Link href="/(app)/units" asChild>
        <Pressable style={styles.card}>
          <Text style={styles.cardTitle}>Units</Text>
          <Text style={styles.cardBody}>Manage measurement units for products.</Text>
        </Pressable>
      </Link>
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
  cardDisabled: {
    opacity: 0.7,
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
