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
import { useCreateRole, usePermissionGroups } from '@/src/hooks/useTeam';

export default function NewRoleScreen() {
  const router = useRouter();
  const groupsQuery = usePermissionGroups();
  const mutation = useCreateRole();
  const groups = groupsQuery.data ?? {};

  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [permissions, setPermissions] = useState<string[]>([]);

  const togglePermission = (permission: string) => {
    setPermissions((current) => (
      current.includes(permission)
        ? current.filter((item) => item !== permission)
        : [...current, permission]
    ));
  };

  return (
    <>
      <Stack.Screen options={{ title: 'New role' }} />
      <ScrollView contentContainerStyle={styles.container}>
        <Text style={styles.label}>Name</Text>
        <TextInput value={name} onChangeText={setName} style={styles.input} />

        <Text style={styles.label}>Description</Text>
        <TextInput
          value={description}
          onChangeText={setDescription}
          multiline
          style={[styles.input, styles.textArea]}
        />

        <Text style={styles.sectionTitle}>Permissions</Text>
        {groupsQuery.isLoading ? (
          <ActivityIndicator style={styles.loader} />
        ) : (
          Object.entries(groups).map(([groupName, groupPermissions]) => (
            <View key={groupName} style={styles.group}>
              <Text style={styles.groupTitle}>{groupName}</Text>
              {groupPermissions.map((permission) => {
                const selected = permissions.includes(permission);
                return (
                  <Pressable
                    key={permission}
                    onPress={() => togglePermission(permission)}
                    style={[styles.permissionRow, selected ? styles.permissionSelected : null]}>
                    <Text style={styles.permissionText}>{permission}</Text>
                    <Text style={styles.checkmark}>{selected ? '✓' : ''}</Text>
                  </Pressable>
                );
              })}
            </View>
          ))
        )}

        <Pressable
          disabled={mutation.isPending || !name.trim()}
          onPress={() => {
            void (async () => {
              try {
                await mutation.mutateAsync({
                  name: name.trim(),
                  description: description.trim() || null,
                  permissions,
                });
                router.back();
              } catch (error) {
                const message = error instanceof ApiError ? error.message : 'Could not create role.';
                Alert.alert('Create failed', message);
              }
            })();
          }}
          style={[styles.button, mutation.isPending ? styles.buttonDisabled : null]}>
          <Text style={styles.buttonText}>{mutation.isPending ? 'Saving…' : 'Create role'}</Text>
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
  textArea: {
    minHeight: 80,
    textAlignVertical: 'top',
  },
  sectionTitle: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 12,
    marginTop: 20,
  },
  loader: {
    marginVertical: 16,
  },
  group: {
    marginBottom: 16,
  },
  groupTitle: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '600',
    marginBottom: 8,
    textTransform: 'uppercase',
  },
  permissionRow: {
    alignItems: 'center',
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 8,
    borderWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 6,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  permissionSelected: {
    backgroundColor: '#eff6ff',
    borderColor: '#2563eb',
  },
  permissionText: {
    color: '#334155',
    flex: 1,
    fontSize: 14,
  },
  checkmark: {
    color: '#2563eb',
    fontSize: 16,
    fontWeight: '700',
    marginLeft: 8,
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
