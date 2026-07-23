import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { ActivityIndicator, Alert, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { useAuth } from '@/src/auth/AuthContext';
import { useDeleteProduct, useProduct } from '@/src/hooks/useProducts';
import { canDeleteInventory, canUpdateInventory } from '@/src/permissions';

export default function ProductDetailScreen() {
  const router = useRouter();
  const { permissions } = useAuth();
  const { id } = useLocalSearchParams<{ id: string }>();
  const productId = Number(id);
  const query = useProduct(Number.isFinite(productId) ? productId : null);
  const deleteMutation = useDeleteProduct();

  if (query.isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  if (!query.data) {
    return (
      <View style={styles.centered}>
        <Text style={styles.empty}>Product not found.</Text>
      </View>
    );
  }

  const product = query.data;

  const handleDelete = () => {
    Alert.alert('Delete product', `Delete ${product.name}?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Delete',
        style: 'destructive',
        onPress: () => {
          void deleteMutation.mutateAsync(product.id).then(() => {
            router.replace('/(app)/products');
          });
        },
      },
    ]);
  };

  return (
    <>
      <Stack.Screen
        options={{
          title: product.name,
          headerRight: () => (
            canUpdateInventory(permissions) ? (
              <Pressable
                onPress={() => router.push(`/(app)/products/${product.id}/edit`)}
                style={styles.headerAction}>
                <Text style={styles.headerActionText}>Edit</Text>
              </Pressable>
            ) : null
          ),
        }}
      />

      <ScrollView contentContainerStyle={styles.container}>
        <DetailRow label="SKU" value={product.sku ?? '—'} />
        <DetailRow label="Barcode" value={product.barcode ?? '—'} />
        <DetailRow label="Cost price" value={product.cost_price} />
        <DetailRow label="Selling price" value={product.selling_price} />
        <DetailRow label="Tax rate" value={`${product.tax_rate}%`} />
        <DetailRow
          label="Reorder point"
          value={product.reorder_point === null ? '—' : String(product.reorder_point)}
        />
        <DetailRow label="Status" value={product.is_active ? 'Active' : 'Inactive'} />

        {canDeleteInventory(permissions) ? (
          <Pressable
            disabled={deleteMutation.isPending}
            onPress={handleDelete}
            style={styles.deleteButton}>
            <Text style={styles.deleteButtonText}>
              {deleteMutation.isPending ? 'Deleting…' : 'Delete product'}
            </Text>
          </Pressable>
        ) : null}
      </ScrollView>
    </>
  );
}

function DetailRow({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.row}>
      <Text style={styles.label}>{label}</Text>
      <Text style={styles.value}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  centered: {
    alignItems: 'center',
    flex: 1,
    justifyContent: 'center',
    padding: 24,
  },
  empty: {
    color: '#64748b',
    fontSize: 15,
  },
  container: {
    padding: 16,
    paddingBottom: 40,
  },
  row: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 10,
    borderWidth: 1,
    marginBottom: 10,
    padding: 14,
  },
  label: {
    color: '#64748b',
    fontSize: 13,
    marginBottom: 4,
  },
  value: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '600',
  },
  headerAction: {
    marginRight: 16,
  },
  headerActionText: {
    color: '#2563eb',
    fontSize: 16,
    fontWeight: '600',
  },
  deleteButton: {
    alignItems: 'center',
    backgroundColor: '#fee2e2',
    borderRadius: 10,
    marginTop: 16,
    paddingVertical: 14,
  },
  deleteButtonText: {
    color: '#b91c1c',
    fontSize: 16,
    fontWeight: '700',
  },
});
