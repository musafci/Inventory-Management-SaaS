import { Stack, useRouter } from 'expo-router';
import { useState } from 'react';
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
import { useCreateTeamMember, useRoles } from '@/src/hooks/useTeam';

export default function NewTeamMemberScreen() {
  const router = useRouter();
  const rolesQuery = useRoles();
  const mutation = useCreateTeamMember();
  const roles = rolesQuery.data ?? [];

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [phone, setPhone] = useState('');
  const [role, setRole] = useState('');

  return (
    <>
      <Stack.Screen options={{ title: 'Add team member' }} />
      <ScrollView contentContainerStyle={styles.container}>
        <Text style={styles.label}>Name</Text>
        <TextInput value={name} onChangeText={setName} style={styles.input} />

        <Text style={styles.label}>Email</Text>
        <TextInput
          value={email}
          onChangeText={setEmail}
          keyboardType="email-address"
          autoCapitalize="none"
          style={styles.input}
        />

        <Text style={styles.label}>Password</Text>
        <TextInput
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          autoCapitalize="none"
          style={styles.input}
        />

        <Text style={styles.label}>Phone</Text>
        <TextInput
          value={phone}
          onChangeText={setPhone}
          keyboardType="phone-pad"
          style={styles.input}
        />

        <Text style={styles.label}>Role</Text>
        {rolesQuery.isLoading ? (
          <ActivityIndicator style={styles.loader} />
        ) : (
          <View style={styles.roleList}>
            {roles.map((item) => (
              <Pressable
                key={item.id}
                onPress={() => setRole(item.name)}
                style={[styles.roleOption, role === item.name ? styles.roleSelected : null]}>
                <Text style={[styles.roleText, role === item.name ? styles.roleTextSelected : null]}>
                  {item.name}
                </Text>
              </Pressable>
            ))}
          </View>
        )}

        <Pressable
          disabled={mutation.isPending || !name.trim() || !email.trim() || !password.trim() || !role}
          onPress={() => {
            void (async () => {
              try {
                await mutation.mutateAsync({
                  name: name.trim(),
                  email: email.trim(),
                  password,
                  phone: phone.trim() || null,
                  role,
                });
                router.back();
              } catch (error) {
                const message = error instanceof ApiError ? error.message : 'Could not add team member.';
                Alert.alert('Create failed', message);
              }
            })();
          }}
          style={[styles.button, mutation.isPending ? styles.buttonDisabled : null]}>
          <Text style={styles.buttonText}>{mutation.isPending ? 'Saving…' : 'Add member'}</Text>
        </Pressable>
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
  label: {
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
  loader: {
    marginVertical: 12,
  },
  roleList: {
    gap: 8,
  },
  roleOption: {
    backgroundColor: '#fff',
    borderColor: '#cbd5e1',
    borderRadius: 10,
    borderWidth: 1,
    paddingHorizontal: 12,
    paddingVertical: 12,
  },
  roleSelected: {
    backgroundColor: '#eff6ff',
    borderColor: '#2563eb',
  },
  roleText: {
    color: '#334155',
    fontSize: 15,
    fontWeight: '600',
  },
  roleTextSelected: {
    color: '#2563eb',
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
