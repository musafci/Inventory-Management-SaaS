import { Redirect, Stack } from 'expo-router';
import { ActivityIndicator, View } from 'react-native';

import { ImpersonationBanner } from '@/components/ImpersonationBanner';
import { OfflineBanner } from '@/components/OfflineBanner';
import { useAuth } from '@/src/auth/AuthContext';

export default function AppLayout() {
  const { isAuthenticated, isLoading } = useAuth();

  if (isLoading) {
    return (
      <View style={{ alignItems: 'center', flex: 1, justifyContent: 'center' }}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  if (!isAuthenticated) {
    return <Redirect href="/(auth)/login" />;
  }

  return (
    <>
      <ImpersonationBanner />
      <OfflineBanner />
      <Stack>
        <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
        <Stack.Screen name="products/index" options={{ title: 'Products' }} />
        <Stack.Screen name="products/new" options={{ title: 'New product' }} />
        <Stack.Screen name="products/[id]" options={{ title: 'Product' }} />
        <Stack.Screen name="products/[id]/edit" options={{ title: 'Edit product' }} />
        <Stack.Screen name="stocks/index" options={{ title: 'Stock levels' }} />
        <Stack.Screen name="stock-movements/index" options={{ title: 'Stock movements' }} />
        <Stack.Screen name="stock-movements/new" options={{ title: 'Record adjustment' }} />
        <Stack.Screen name="categories/index" options={{ title: 'Categories' }} />
        <Stack.Screen name="categories/new" options={{ title: 'New category' }} />
        <Stack.Screen name="categories/[id]/edit" options={{ title: 'Edit category' }} />
        <Stack.Screen name="units/index" options={{ title: 'Units' }} />
        <Stack.Screen name="units/new" options={{ title: 'New unit' }} />
        <Stack.Screen name="units/[id]/edit" options={{ title: 'Edit unit' }} />
      </Stack>
    </>
  );
}
