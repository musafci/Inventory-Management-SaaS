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
  View,
} from 'react-native';
import { Link, Redirect, Stack } from 'expo-router';

import { ApiError } from '@/src/api/client';
import { useAuth } from '@/src/auth/AuthContext';

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
        <ScrollView contentContainerStyle={styles.scrollContent}>
          <View style={styles.card}>
            <Text style={styles.title}>Start your trial</Text>
            <Text style={styles.subtitle}>Create an organization and owner account</Text>

            <TextInput
              autoCapitalize="words"
              placeholder="Organization name"
              style={styles.input}
              value={organizationName}
              onChangeText={setOrganizationName}
            />
            <TextInput
              autoCapitalize="words"
              placeholder="Your name"
              style={styles.input}
              value={name}
              onChangeText={setName}
            />
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
              keyboardType="phone-pad"
              placeholder="Phone (optional)"
              style={styles.input}
              value={phone}
              onChangeText={setPhone}
            />
            <TextInput
              autoCapitalize="none"
              placeholder="Password (min 8 characters)"
              secureTextEntry
              style={styles.input}
              value={password}
              onChangeText={setPassword}
            />
            <TextInput
              autoCapitalize="none"
              placeholder="Confirm password"
              secureTextEntry
              style={styles.input}
              value={passwordConfirmation}
              onChangeText={setPasswordConfirmation}
            />

            {error ? <Text style={styles.error}>{error}</Text> : null}

            <Pressable
              disabled={
                submitting ||
                organizationName.trim() === '' ||
                name.trim() === '' ||
                email.trim() === '' ||
                password === '' ||
                passwordConfirmation === ''
              }
              onPress={handleSubmit}
              style={[styles.button, submitting && styles.buttonDisabled]}>
              {submitting ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={styles.buttonText}>Create account</Text>
              )}
            </Pressable>

            <Link href="/(auth)/login" style={styles.link}>
              Already have an account? Sign in
            </Link>
          </View>
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
    flexGrow: 1,
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
  link: {
    color: '#2563eb',
    fontSize: 15,
    fontWeight: '600',
    marginTop: 20,
    textAlign: 'center',
  },
});
