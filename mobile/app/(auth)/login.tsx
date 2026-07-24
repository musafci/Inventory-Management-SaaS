import { useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { Link, Redirect } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { SymbolView } from 'expo-symbols';

import { Button, Card, Input } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import { getApiBaseUrl } from '@/src/api/config';
import { useAuth } from '@/src/auth/AuthContext';
import { palette, shadow, theme } from '@/src/theme';

export default function LoginScreen() {
  const { isAuthenticated, isLoading, login } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  if (!isLoading && isAuthenticated) {
    return <Redirect href="/(app)/(tabs)" />;
  }

  async function handleSubmit() {
    setSubmitting(true);
    setError(null);

    try {
      await login(email.trim(), password);
    } catch (caught) {
      if (caught instanceof ApiError) {
        setError(caught.message);
      } else {
        setError('Unable to sign in. Check your connection and try again.');
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
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
          <Text style={styles.title}>Welcome back</Text>
          <Text style={styles.subtitle}>Sign in to your organization workspace</Text>

          <Input
            accessibilityLabel="Email address"
            autoCapitalize="none"
            autoComplete="email"
            keyboardType="email-address"
            label="Email"
            placeholder="you@company.com"
            testID="login-email"
            value={email}
            onChangeText={setEmail}
          />

          <Input
            accessibilityLabel="Password"
            autoCapitalize="none"
            label="Password"
            placeholder="Enter your password"
            secureTextEntry
            testID="login-password"
            value={password}
            onChangeText={setPassword}
          />

          {error ? <Text accessibilityLiveRegion="polite" style={styles.error}>{error}</Text> : null}

          <Button
            accessibilityLabel="Sign in"
            disabled={email.trim() === '' || password === ''}
            label="Sign in"
            loading={submitting}
            onPress={handleSubmit}
            testID="login-submit"
          />

          <View style={styles.links}>
            <Link href="/(auth)/forgot-password" style={styles.link}>
              Forgot password?
            </Link>
            <Link href="/(auth)/register" style={styles.link}>
              Create an account
            </Link>
          </View>

          <Text style={styles.apiHint}>API: {getApiBaseUrl()}</Text>
        </Card>
      </View>
    </KeyboardAvoidingView>
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
  links: {
    gap: theme.spacing.md,
    marginTop: theme.spacing.lg,
  },
  link: {
    color: theme.colors.primary,
    fontSize: 15,
    fontWeight: '700',
    textAlign: 'center',
  },
  apiHint: {
    color: theme.colors.textMuted,
    fontSize: 11,
    marginTop: theme.spacing.lg,
    textAlign: 'center',
  },
});
