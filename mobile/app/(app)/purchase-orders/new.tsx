import { Stack, useRouter } from 'expo-router';

import { PurchaseOrderForm } from '@/components/PurchaseOrderForm';

export default function NewPurchaseOrderScreen() {
  const router = useRouter();

  return (
    <>
      <Stack.Screen options={{ title: 'New purchase order' }} />
      <PurchaseOrderForm
        onSuccess={(orderId) => {
          router.replace(`/(app)/purchase-orders/${orderId}`);
        }}
      />
    </>
  );
}
