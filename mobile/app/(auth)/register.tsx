import { useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { Link, Redirect, Stack } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { SymbolView } from 'expo-symbols';

import { Button, Card, Input } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import { useAuth } from '@/src/auth/AuthContext';
import { gradients, palette, shadow, theme } from '@/src/theme';

export default function RegisterScreen() {
  const { isAuthenticated, isLoading, register } = useAuth();
  const [organizationName, setOrganizationName] = useState('');
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  if (!isLoading && isAuthenticated) {
    return <Redirect href="/(app)/(tabs)" />;
  }

  async function handleSubmit() {
    setSubmitting(true);
    setError(null);

    try {
      await register({
        organization_name: organizationName.trim(),
        name: name.trim(),
        email: email.trim(),
        phone: phone.trim() || null,
        password,
        password_confirmation: passwordConfirmation,
      });
    } catch (caught) {
      if (caught instanceof ApiError) {
        setError(caught.message);
      } else {
        setError('Unable to register. Check your connection and try again.');
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Create account' }} />
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        style={styles.container}>
        <LinearGradient
          colors={[...gradients.authBackground]}
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
            <Text style={styles.title}>Start your trial</Text>
            <Text style={styles.subtitle}>Create an organization and owner account</Text>

            <Input
              autoCapitalize="words"
              label="Organization name"
              placeholder="Acme Inc."
              value={organizationName}
              onChangeText={setOrganizationName}
            />
            <Input
              autoCapitalize="words"
              label="Your name"
              placeholder="Jane Smith"
              value={name}
              onChangeText={setName}
            />
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
              keyboardType="phone-pad"
              label="Phone"
              placeholder="Optional"
              value={phone}
              onChangeText={setPhone}
            />
            <Input
              autoCapitalize="none"
              label="Password"
              placeholder="Min 8 characters"
              secureTextEntry
              value={password}
              onChangeText={setPassword}
            />
            <Input
              autoCapitalize="none"
              label="Confirm password"
              placeholder="Re-enter password"
              secureTextEntry
              value={passwordConfirmation}
              onChangeText={setPasswordConfirmation}
            />

            {error ? <Text accessibilityLiveRegion="polite" style={styles.error}>{error}</Text> : null}

            <Button
              disabled={
                organizationName.trim() === '' ||
                name.trim() === '' ||
                email.trim() === '' ||
                password === '' ||
                passwordConfirmation === ''
              }
              label="Create account"
              loading={submitting}
              onPress={handleSubmit}
            />

            <Link href="/(auth)/login" style={styles.link}>
              Already have an account? Sign in
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
