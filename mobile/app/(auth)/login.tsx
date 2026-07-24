import { useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { Redirect } from 'expo-router';
import { Link } from 'expo-router';

import { ApiError } from '@/src/api/client';
import { getApiBaseUrl } from '@/src/api/config';
import { useAuth } from '@/src/auth/AuthContext';

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
      <View style={styles.card}>
        <Text style={styles.title}>Oneapp Inventory</Text>
        <Text style={styles.subtitle}>Sign in to your organization</Text>

        <TextInput
          accessibilityLabel="Email address"
          autoCapitalize="none"
          autoComplete="email"
          keyboardType="email-address"
          placeholder="Email"
          style={styles.input}
          testID="login-email"
          value={email}
          onChangeText={setEmail}
        />

        <TextInput
          accessibilityLabel="Password"
          autoCapitalize="none"
          placeholder="Password"
          secureTextEntry
          style={styles.input}
          testID="login-password"
          value={password}
          onChangeText={setPassword}
        />

        {error ? <Text accessibilityLiveRegion="polite" style={styles.error}>{error}</Text> : null}

        <Pressable
          accessibilityLabel="Sign in"
          accessibilityRole="button"
          disabled={submitting || email.trim() === '' || password === ''}
          onPress={handleSubmit}
          style={[styles.button, submitting && styles.buttonDisabled]}
          testID="login-submit">
          {submitting ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.buttonText}>Sign in</Text>
          )}
        </Pressable>

        <Link href="/(auth)/forgot-password" style={styles.link}>
          Forgot password?
        </Link>
        <Link href="/(auth)/register" style={styles.link}>
          Create an account
        </Link>

        <Text style={styles.apiHint}>API: {getApiBaseUrl()}</Text>
      </View>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f8fafc',
    flex: 1,
    justifyContent: 'center',
    padding: 24,
  },
  card: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 20,
    borderWidth: 1,
    padding: 24,
  },
  title: {
    color: '#0f172a',
    fontSize: 28,
    fontWeight: '700',
  },
  subtitle: {
    color: '#64748b',
    fontSize: 15,
    marginBottom: 24,
    marginTop: 8,
  },
  input: {
    backgroundColor: '#fff',
    borderColor: '#cbd5e1',
    borderRadius: 12,
    borderWidth: 1,
    fontSize: 16,
    marginBottom: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
  },
  error: {
    color: '#dc2626',
    fontSize: 14,
    marginBottom: 12,
  },
  button: {
    alignItems: 'center',
    backgroundColor: '#4f46e5',
    borderRadius: 12,
    marginTop: 4,
    paddingVertical: 14,
  },
  buttonDisabled: {
    opacity: 0.7,
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  apiHint: {
    color: '#94a3b8',
    fontSize: 11,
    marginTop: 16,
    textAlign: 'center',
  },
  link: {
    color: '#2563eb',
    fontSize: 15,
    fontWeight: '600',
    marginTop: 16,
    textAlign: 'center',
  },
});
