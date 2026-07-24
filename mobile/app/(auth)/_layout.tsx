import { Redirect, Stack } from 'expo-router';

export default function AuthLayout() {
  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="login" />
      <Stack.Screen name="register" options={{ headerShown: true, title: 'Create account' }} />
      <Stack.Screen name="forgot-password" options={{ headerShown: true, title: 'Forgot password' }} />
      <Stack.Screen name="reset-password" options={{ headerShown: true, title: 'Reset password' }} />
    </Stack>
  );
}
