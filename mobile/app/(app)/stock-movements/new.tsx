import { Stack, useRouter } from 'expo-router';

import { StockMovementForm } from '@/components/StockMovementForm';

export default function NewStockMovementScreen() {
  const router = useRouter();

  return (
    <>
      <Stack.Screen options={{ title: 'Record adjustment' }} />
      <StockMovementForm
        onSuccess={() => {
          router.replace('/(app)/stock-movements');
        }}
      />
    </>
  );
}
