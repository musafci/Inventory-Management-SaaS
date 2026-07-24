import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { ApiError } from '@/src/api/client';
import { useTeamMembers, useUpdateTeamMemberRole, useRoles } from '@/src/hooks/useTeam';

export default function EditTeamMemberScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const userId = Number(id);
  const membersQuery = useTeamMembers();
  const rolesQuery = useRoles();
  const mutation = useUpdateTeamMemberRole();
  const member = membersQuery.data?.find((item) => item.id === userId);
  const roles = rolesQuery.data ?? [];
  const [role, setRole] = useState('');

  useEffect(() => {
    if (member?.role) {
      setRole(member.role);
    }
  }, [member?.role]);

  if (membersQuery.isLoading) {
    return (
      <View style={styles.loading}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  if (!member) {
    return null;
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Edit team member' }} />
      <ScrollView contentContainerStyle={styles.container}>
        <Text style={styles.label}>Name</Text>
        <Text style={styles.value}>{member.name}</Text>

        <Text style={styles.label}>Email</Text>
        <Text style={styles.value}>{member.email}</Text>

        <Text style={styles.label}>Role</Text>
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

        <Pressable
          disabled={mutation.isPending || !role}
          onPress={() => {
            void (async () => {
              try {
                await mutation.mutateAsync({ userId, role });
                router.back();
              } catch (error) {
                const message = error instanceof ApiError ? error.message : 'Could not update member.';
                Alert.alert('Update failed', message);
              }
            })();
          }}
          style={[styles.button, mutation.isPending ? styles.buttonDisabled : null]}>
          <Text style={styles.buttonText}>{mutation.isPending ? 'Saving…' : 'Save changes'}</Text>
        </Pressable>
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  loading: {
    alignItems: 'center',
    flex: 1,
    justifyContent: 'center',
  },
  container: {
    backgroundColor: '#f8fafc',
    flexGrow: 1,
    padding: 16,
    paddingBottom: 40,
  },
  label: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '600',
    marginBottom: 6,
    marginTop: 12,
    textTransform: 'uppercase',
  },
  value: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '600',
  },
  roleList: {
    gap: 8,
    marginTop: 4,
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
