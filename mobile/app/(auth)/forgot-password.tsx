import { useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { Link, Stack } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';

import { Button, Card, Input } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import * as authApi from '@/src/api/auth';
import { palette, shadow, theme } from '@/src/theme';

export default function ForgotPasswordScreen() {
  const [email, setEmail] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  async function handleSubmit() {
    setSubmitting(true);
    setError(null);
    setSuccess(null);

    try {
      const result = await authApi.forgotPassword(email.trim());
      setSuccess(result.message);
    } catch (caught) {
      if (caught instanceof ApiError) {
        setError(caught.message);
      } else {
        setError('Unable to send reset link. Try again.');
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Forgot password' }} />
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
          <Card style={[styles.card, shadow('lg')]}>
            <Text style={styles.title}>Reset password</Text>
            <Text style={styles.subtitle}>
              Enter your email and we will send a reset link if an account exists.
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

            {error ? <Text style={styles.error}>{error}</Text> : null}
            {success ? <Text style={styles.success}>{success}</Text> : null}

            <Button
              disabled={email.trim() === ''}
              label="Send reset link"
              loading={submitting}
              onPress={handleSubmit}
            />

            <Link href="/(auth)/reset-password" style={styles.link}>
              Have a reset token? Enter it here
            </Link>
            <Link href="/(auth)/login" style={styles.secondaryLink}>
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
  success: {
    color: theme.colors.success,
    fontSize: 14,
    lineHeight: 20,
    marginBottom: theme.spacing.md,
  },
  link: {
    color: theme.colors.primary,
    fontSize: 15,
    fontWeight: '700',
    marginTop: theme.spacing.lg,
    textAlign: 'center',
  },
  secondaryLink: {
    color: theme.colors.textSecondary,
    fontSize: 15,
    marginTop: theme.spacing.md,
    textAlign: 'center',
  },
});
