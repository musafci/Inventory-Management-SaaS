import { useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { Link, Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { SymbolView } from 'expo-symbols';

import { Button, Card, Input } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import * as authApi from '@/src/api/auth';
import { palette, shadow, theme } from '@/src/theme';

export default function ResetPasswordScreen() {
  const router = useRouter();
  const params = useLocalSearchParams<{ email?: string; token?: string }>();
  const [email, setEmail] = useState(params.email ?? '');
  const [token, setToken] = useState(params.token ?? '');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit() {
    setSubmitting(true);
    setError(null);

    try {
      await authApi.resetPassword({
        email: email.trim(),
        token: token.trim(),
        password,
        password_confirmation: passwordConfirmation,
      });
      router.replace('/(auth)/login');
    } catch (caught) {
      if (caught instanceof ApiError) {
        setError(caught.message);
      } else {
        setError('Unable to reset password. Check the token and try again.');
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Reset password' }} />
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        style={styles.container}>
        <LinearGradient
          colors={[palette.primary600, '#818cf8', palette.slate100]}
          end={{ x: 0.5, y: 1 }}
          start={{ x: 0, y: 0 }}
          style={StyleSheet.absoluteFill}
        />

        <View style={styles.content}>
          <View style={styles.brandRow}>
            <View style={styles.logoWrap}>
              <SymbolView
                name={{ ios: 'shippingbox.fill', android: 'inventory_2', web: 'inventory_2' }}
                size={28}
                tintColor={theme.colors.primaryText}
              />
            </View>
            <View>
              <Text style={styles.brandTitle}>Oneapp Inventory</Text>
              <Text style={styles.brandSubtitle}>Modern inventory for growing teams</Text>
            </View>
          </View>

          <Card style={[styles.card, shadow('lg')]}>
            <Text style={styles.title}>Set a new password</Text>
            <Text style={styles.subtitle}>
              Paste the token from your reset email along with your account email.
            </Text>

            <Input
              autoCapitalize="none"
              autoComplete="email"
              keyboardType="email-address"
              label="Email"
              placeholder="you@company.com"
              value={email}
              onChangeText={setEmail}
            />
            <Input
              autoCapitalize="none"
              label="Reset token"
              placeholder="Paste token from email"
              value={token}
              onChangeText={setToken}
            />
            <Input
              autoCapitalize="none"
              label="New password"
              placeholder="Enter new password"
              secureTextEntry
              value={password}
              onChangeText={setPassword}
            />
            <Input
              autoCapitalize="none"
              label="Confirm new password"
              placeholder="Re-enter new password"
              secureTextEntry
              value={passwordConfirmation}
              onChangeText={setPasswordConfirmation}
            />

            {error ? <Text accessibilityLiveRegion="polite" style={styles.error}>{error}</Text> : null}

            <Button
              disabled={
                email.trim() === '' ||
                token.trim() === '' ||
                password === '' ||
                passwordConfirmation === ''
              }
              label="Reset password"
              loading={submitting}
              onPress={handleSubmit}
            />

            <Link href="/(auth)/login" style={styles.link}>
              Back to sign in
            </Link>
          </Card>
        </View>
      </KeyboardAvoidingView>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  content: {
    flex: 1,
    justifyContent: 'center',
    padding: theme.spacing.xl,
  },
  brandRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: theme.spacing.md,
    marginBottom: theme.spacing.xl,
  },
  logoWrap: {
    alignItems: 'center',
    backgroundColor: palette.primary600,
    borderRadius: theme.radius.lg,
    height: 56,
    justifyContent: 'center',
    width: 56,
    ...shadow('md'),
  },
  brandTitle: {
    color: theme.colors.text,
    fontSize: 24,
    fontWeight: '800',
  },
  brandSubtitle: {
    color: theme.colors.textSecondary,
    fontSize: 14,
    marginTop: 2,
  },
  card: {
    padding: theme.spacing.xxl,
  },
  title: {
    ...theme.typography.heading,
    color: theme.colors.text,
    fontSize: 24,
  },
  subtitle: {
    ...theme.typography.body,
    color: theme.colors.textSecondary,
    marginBottom: theme.spacing.lg,
    marginTop: theme.spacing.sm,
  },
  error: {
    color: theme.colors.danger,
    fontSize: 14,
    marginBottom: theme.spacing.md,
  },
  link: {
    color: theme.colors.primary,
    fontSize: 15,
    fontWeight: '700',
    marginTop: theme.spacing.lg,
    textAlign: 'center',
  },
});
