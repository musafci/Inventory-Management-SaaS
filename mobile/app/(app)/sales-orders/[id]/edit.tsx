import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { ActivityIndicator, View } from 'react-native';

import { SalesOrderForm } from '@/components/SalesOrderForm';
import { useSalesOrder } from '@/src/hooks/useOrders';

export default function EditSalesOrderScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const orderId = Number(id);
  const query = useSalesOrder(Number.isFinite(orderId) ? orderId : null);

  if (query.isLoading) {
    return (
      <View style={{ alignItems: 'center', flex: 1, justifyContent: 'center' }}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  if (!query.data || query.data.status !== 'draft') {
    return null;
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Edit sales order' }} />
      <SalesOrderForm
        order={query.data}
        onSuccess={() => {
          router.back();
        }}
      />
    </>
  );
}
