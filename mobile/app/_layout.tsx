import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useFonts } from 'expo-font';
import { Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { useEffect, useState } from 'react';
import 'react-native-reanimated';

import { AuthProvider } from '@/src/auth/AuthContext';
import { NetworkProvider } from '@/src/network/NetworkContext';
import { SyncProvider } from '@/src/sync/SyncContext';
import { ToastProvider } from '@/src/toast/ToastContext';

export {
  ErrorBoundary,
} from 'expo-router';

SplashScreen.preventAutoHideAsync();

export default function RootLayout() {
  const [queryClient] = useState(
    () => new QueryClient({
      defaultOptions: {
        queries: {
          retry: 1,
          staleTime: 30_000,
        },
      },
    }),
  );
  const [loaded, error] = useFonts({
    SpaceMono: require('../assets/fonts/SpaceMono-Regular.ttf'),
  });

  useEffect(() => {
    if (error) {
      throw error;
    }
  }, [error]);

  useEffect(() => {
    if (loaded) {
      SplashScreen.hideAsync();
    }
  }, [loaded]);

  if (!loaded) {
    return null;
  }

  return (
    <QueryClientProvider client={queryClient}>
      <NetworkProvider>
        <AuthProvider>
          <SyncProvider>
            <ToastProvider>
              <Stack screenOptions={{ headerShown: false }}>
                <Stack.Screen name="index" />
                <Stack.Screen name="(auth)" />
                <Stack.Screen name="(app)" />
              </Stack>
            </ToastProvider>
          </SyncProvider>
        </AuthProvider>
      </NetworkProvider>
    </QueryClientProvider>
  );
}
