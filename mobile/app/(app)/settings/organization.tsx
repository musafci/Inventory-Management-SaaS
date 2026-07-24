import { Stack } from 'expo-router';
import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';

import { ApiError } from '@/src/api/client';
import { useAuth } from '@/src/auth/AuthContext';
import { useOrganization, useUpdateOrganization } from '@/src/hooks/useOrganization';
import { canUpdateOrganization } from '@/src/permissions';

export default function OrganizationSettingsScreen() {
  const { permissions } = useAuth();
  const query = useOrganization();
  const updateMutation = useUpdateOrganization();
  const org = query.data;
  const canEdit = canUpdateOrganization(permissions);

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');

  useEffect(() => {
    if (org) {
      setName(org.name);
      setEmail(org.email);
      setPhone(org.phone ?? '');
    }
  }, [org]);

  return (
    <>
      <Stack.Screen options={{ title: 'Organization' }} />
      <ScrollView contentContainerStyle={styles.container}>
        {query.isLoading ? (
          <ActivityIndicator size="large" style={styles.loader} />
        ) : query.isError ? (
          <Text style={styles.error}>Could not load organization.</Text>
        ) : org ? (
          <>
            <View style={styles.card}>
              <Text style={styles.label}>Plan</Text>
              <Text style={styles.value}>{org.plan}</Text>
              <Text style={styles.meta}>Status: {org.status}</Text>
              {org.slug ? <Text style={styles.meta}>Slug: {org.slug}</Text> : null}
              {org.users_count !== undefined ? (
                <Text style={styles.meta}>{org.users_count} team members</Text>
              ) : null}
            </View>

            {canEdit ? (
              <>
                <Text style={styles.fieldLabel}>Name</Text>
                <TextInput
                  value={name}
                  onChangeText={setName}
                  style={styles.input}
                />

                <Text style={styles.fieldLabel}>Email</Text>
                <TextInput
                  value={email}
                  onChangeText={setEmail}
                  keyboardType="email-address"
                  autoCapitalize="none"
                  style={styles.input}
                />

                <Text style={styles.fieldLabel}>Phone</Text>
                <TextInput
                  value={phone}
                  onChangeText={setPhone}
                  keyboardType="phone-pad"
                  style={styles.input}
                />

                <Pressable
                  disabled={updateMutation.isPending}
                  onPress={() => {
                    void (async () => {
                      try {
                        await updateMutation.mutateAsync({
                          name: name.trim(),
                          email: email.trim(),
                          phone: phone.trim() || null,
                        });
                        Alert.alert('Saved', 'Organization updated.');
                      } catch (error) {
                        const message = error instanceof ApiError
                          ? error.message
                          : 'Could not update organization.';
                        Alert.alert('Update failed', message);
                      }
                    })();
                  }}
                  style={[styles.button, updateMutation.isPending ? styles.buttonDisabled : null]}>
                  <Text style={styles.buttonText}>
                    {updateMutation.isPending ? 'Saving…' : 'Save changes'}
                  </Text>
                </Pressable>
              </>
            ) : (
              <View style={styles.card}>
                <Text style={styles.label}>Name</Text>
                <Text style={styles.value}>{org.name}</Text>
                <Text style={styles.label}>Email</Text>
                <Text style={styles.value}>{org.email}</Text>
                {org.phone ? (
                  <>
                    <Text style={styles.label}>Phone</Text>
                    <Text style={styles.value}>{org.phone}</Text>
                  </>
                ) : null}
              </View>
            )}
          </>
        ) : null}
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f8fafc',
    flexGrow: 1,
    padding: 16,
    paddingBottom: 40,
  },
  loader: {
    marginTop: 32,
  },
  error: {
    color: '#b91c1c',
    fontSize: 15,
    padding: 16,
  },
  card: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 16,
    padding: 16,
  },
  label: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '600',
    marginTop: 12,
    textTransform: 'uppercase',
  },
  value: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '600',
    marginTop: 4,
  },
  meta: {
    color: '#64748b',
    fontSize: 13,
    marginTop: 6,
  },
  fieldLabel: {
    color: '#334155',
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 8,
    marginTop: 12,
  },
  input: {
    backgroundColor: '#fff',
    borderColor: '#cbd5e1',
    borderRadius: 10,
    borderWidth: 1,
    fontSize: 16,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  button: {
    alignItems: 'center',
    backgroundColor: '#2563eb',
    borderRadius: 10,
    marginTop: 24,
    paddingVertical: 14,
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '700',
  },
});
