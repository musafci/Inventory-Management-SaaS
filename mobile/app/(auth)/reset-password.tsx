import { useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
} from 'react-native';
import { Link, Stack, useLocalSearchParams, useRouter } from 'expo-router';

import { ApiError } from '@/src/api/client';
import * as authApi from '@/src/api/auth';

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
        <ScrollView contentContainerStyle={styles.scrollContent}>
          <Text style={styles.title}>Set a new password</Text>
          <Text style={styles.subtitle}>
            Paste the token from your reset email along with your account email.
          </Text>

          <TextInput
            autoCapitalize="none"
            autoComplete="email"
            keyboardType="email-address"
            placeholder="Email"
            style={styles.input}
            value={email}
            onChangeText={setEmail}
          />
          <TextInput
            autoCapitalize="none"
            placeholder="Reset token"
            style={styles.input}
            value={token}
            onChangeText={setToken}
          />
          <TextInput
            autoCapitalize="none"
            placeholder="New password"
            secureTextEntry
            style={styles.input}
            value={password}
            onChangeText={setPassword}
          />
          <TextInput
            autoCapitalize="none"
            placeholder="Confirm new password"
            secureTextEntry
            style={styles.input}
            value={passwordConfirmation}
            onChangeText={setPasswordConfirmation}
          />

          {error ? <Text style={styles.error}>{error}</Text> : null}

          <Pressable
            disabled={
              submitting ||
              email.trim() === '' ||
              token.trim() === '' ||
              password === '' ||
              passwordConfirmation === ''
            }
            onPress={handleSubmit}
            style={[styles.button, submitting && styles.buttonDisabled]}>
            {submitting ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={styles.buttonText}>Reset password</Text>
            )}
          </Pressable>

          <Link href="/(auth)/login" style={styles.link}>
            Back to sign in
          </Link>
        </ScrollView>
      </KeyboardAvoidingView>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f8fafc',
    flex: 1,
  },
  scrollContent: {
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
    lineHeight: 22,
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
  link: {
    color: '#2563eb',
    fontSize: 15,
    fontWeight: '600',
    marginTop: 20,
    textAlign: 'center',
  },
});
