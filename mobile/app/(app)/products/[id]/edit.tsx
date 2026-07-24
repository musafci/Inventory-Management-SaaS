import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { ActivityIndicator, Alert, View } from 'react-native';

import { ProductForm } from '@/components/ProductForm';
import { ApiError } from '@/src/api/client';
import { useProduct, useUpdateProduct } from '@/src/hooks/useProducts';
import { useToast } from '@/src/toast/ToastContext';

export default function EditProductScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const productId = Number(id);
  const query = useProduct(Number.isFinite(productId) ? productId : null);
  const mutation = useUpdateProduct(productId);
  const toast = useToast();

  if (query.isLoading) {
    return (
      <View style={{ alignItems: 'center', flex: 1, justifyContent: 'center' }}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  if (!query.data) {
    return null;
  }

  const product = query.data;

  return (
    <>
      <Stack.Screen options={{ title: 'Edit product' }} />
      <ProductForm
        submitLabel="Save changes"
        isSubmitting={mutation.isPending}
        initialValues={{
          category_id: product.category_id,
          unit_id: product.unit_id,
          name: product.name,
          sku: product.sku,
          barcode: product.barcode,
          cost_price: product.cost_price,
          selling_price: product.selling_price,
          tax_rate: product.tax_rate,
          reorder_point: product.reorder_point,
          is_active: product.is_active,
        }}
        onSubmit={async (payload) => {
          try {
            await mutation.mutateAsync(payload);
            toast.show('Product updated');
            router.back();
          } catch (error) {
            const message = error instanceof ApiError
              ? error.message
              : 'Could not update product.';

            Alert.alert('Update failed', message);
          }
        }}
      />
    </>
  );
}
