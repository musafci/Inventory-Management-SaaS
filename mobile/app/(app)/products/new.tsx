import { Stack, useRouter } from 'expo-router';
import { Alert } from 'react-native';

import { ProductForm } from '@/components/ProductForm';
import { ApiError } from '@/src/api/client';
import { useCreateProduct } from '@/src/hooks/useProducts';
import { useToast } from '@/src/toast/ToastContext';

export default function NewProductScreen() {
  const router = useRouter();
  const mutation = useCreateProduct();
  const toast = useToast();

  return (
    <>
      <Stack.Screen options={{ title: 'New product' }} />
      <ProductForm
        submitLabel="Create product"
        isSubmitting={mutation.isPending}
        onSubmit={async (payload) => {
          try {
            const product = await mutation.mutateAsync(payload);
            toast.show('Product created');
            router.replace(`/(app)/products/${product.id}`);
          } catch (error) {
            const message = error instanceof ApiError
              ? error.message
              : 'Could not create product.';

            Alert.alert('Create failed', message);
          }
        }}
      />
    </>
  );
}
