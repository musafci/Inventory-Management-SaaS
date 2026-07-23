import { Stack, useRouter } from 'expo-router';

import { SalesOrderForm } from '@/components/SalesOrderForm';

export default function NewSalesOrderScreen() {
  const router = useRouter();

  return (
    <>
      <Stack.Screen options={{ title: 'New sales order' }} />
      <SalesOrderForm
        onSuccess={(orderId) => {
          router.replace(`/(app)/sales-orders/${orderId}`);
        }}
      />
    </>
  );
}
