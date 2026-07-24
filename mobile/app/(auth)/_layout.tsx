import { Stack } from 'expo-router';

import { theme } from '@/src/theme';

export default function AuthLayout() {
  return (
    <Stack
      screenOptions={{
        headerShown: false,
        headerStyle: { backgroundColor: theme.colors.background },
        headerTitleStyle: {
          color: theme.colors.text,
          fontSize: 18,
          fontWeight: '800',
        },
        headerTintColor: theme.colors.primary,
        headerShadowVisible: false,
        contentStyle: { backgroundColor: theme.colors.background },
      }}>
      <Stack.Screen name="login" />
      <Stack.Screen name="register" options={{ headerShown: true, title: 'Create account' }} />
      <Stack.Screen name="forgot-password" options={{ headerShown: true, title: 'Forgot password' }} />
      <Stack.Screen name="reset-password" options={{ headerShown: true, title: 'Reset password' }} />
    </Stack>
  );
}
