import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { Alert } from 'react-native';

import { DetailRow, DetailScreen, ErrorState, HeaderAction, StatusBadge } from '@/components/ui';
import { useAuth } from '@/src/auth/AuthContext';
import { useCategories, useDeleteProduct, useProduct, useUnits } from '@/src/hooks/useProducts';
import { canDeleteInventory, canUpdateInventory } from '@/src/permissions';

export default function ProductDetailScreen() {
  const router = useRouter();
  const { permissions } = useAuth();
  const { id } = useLocalSearchParams<{ id: string }>();
  const productId = Number(id);
  const query = useProduct(Number.isFinite(productId) ? productId : null);
  const categoriesQuery = useCategories();
  const unitsQuery = useUnits();
  const deleteMutation = useDeleteProduct();

  if (!query.isLoading && !query.data) {
    return (
      <>
        <Stack.Screen options={{ title: 'Product' }} />
        <ErrorState message="Product not found." />
      </>
    );
  }

  const product = query.data;
  const categoryName = product
    ? categoriesQuery.data?.find((item) => item.id === product.category_id)?.name
    : undefined;
  const unitName = product
    ? unitsQuery.data?.find((item) => item.id === product.unit_id)?.name
    : undefined;

  const handleDelete = () => {
    if (!product) {
      return;
    }

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
          title: product?.name ?? 'Product',
          headerRight: () => (
            product && canUpdateInventory(permissions) ? (
              <HeaderAction href={`/(app)/products/${product.id}/edit`} label="Edit" />
            ) : null
          ),
        }}
      />

      <DetailScreen
        deleteLabel="Delete product"
        deleteLoading={deleteMutation.isPending}
        loading={query.isLoading}
        showDelete={Boolean(product && canDeleteInventory(permissions))}
        onDelete={handleDelete}>
        {product ? (
          <>
            <DetailRow label="SKU" value={product.sku ?? '—'} />
            <DetailRow label="Barcode" value={product.barcode ?? '—'} />
            <DetailRow label="Category" value={categoryName ?? `#${product.category_id}`} />
            <DetailRow label="Unit" value={unitName ?? `#${product.unit_id}`} />
            <DetailRow label="Cost price" value={product.cost_price} />
            <DetailRow label="Selling price" value={product.selling_price} />
            <DetailRow label="Tax rate" value={`${product.tax_rate}%`} />
            <DetailRow
              label="Reorder point"
              value={product.reorder_point === null ? '—' : String(product.reorder_point)}
            />
            <DetailRow
              label="Status"
              value={
                <StatusBadge
                  label={product.is_active ? 'Active' : 'Inactive'}
                  tone={product.is_active ? 'success' : 'default'}
                />
              }
            />
          </>
        ) : null}
      </DetailScreen>
    </>
  );
}
