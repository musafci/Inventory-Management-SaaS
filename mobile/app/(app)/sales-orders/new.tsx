import { Stack, useRouter } from 'expo-router';

import { SalesOrderForm } from '@/components/SalesOrderForm';

export default function NewSalesOrderScreen() {
  const router = useRouter();

  return (
    <>
      <Stack.Screen options={{ title: 'New sales order' }} />
      <SalesOrderForm
        onSuccess={(orderId) => {
          if (orderId > 0) {
            router.replace(`/(app)/sales-orders/${orderId}`);
          } else {
            router.back();
          }
        }}
      />
    </>
  );
}
