import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect, useMemo, useState } from 'react';
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
import { usePermissionGroups, useRoles, useUpdateRole } from '@/src/hooks/useTeam';

export default function EditRoleScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const roleId = Number(id);
  const rolesQuery = useRoles();
  const groupsQuery = usePermissionGroups();
  const updateMutation = useUpdateRole(roleId);

  const role = useMemo(
    () => rolesQuery.data?.find((item) => item.id === roleId),
    [rolesQuery.data, roleId],
  );
  const groups = groupsQuery.data ?? {};

  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [permissions, setPermissions] = useState<string[]>([]);

  useEffect(() => {
    if (role) {
      setName(role.name);
      setDescription(role.description ?? '');
      setPermissions(role.permissions ?? []);
    }
  }, [role]);

  const togglePermission = (permission: string) => {
    setPermissions((current) => (
      current.includes(permission)
        ? current.filter((item) => item !== permission)
        : [...current, permission]
    ));
  };

  if (rolesQuery.isLoading) {
    return (
      <>
        <Stack.Screen options={{ title: 'Edit role' }} />
        <View style={styles.centered}>
          <ActivityIndicator size="large" />
        </View>
      </>
    );
  }

  if (!role) {
    return (
      <>
        <Stack.Screen options={{ title: 'Edit role' }} />
        <View style={styles.centered}>
          <Text style={styles.error}>Role not found.</Text>
        </View>
      </>
    );
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Edit role' }} />
      <ScrollView contentContainerStyle={styles.container}>
        {role.is_protected ? (
          <View style={styles.notice}>
            <Text style={styles.noticeText}>This is a protected role. Some fields may be restricted.</Text>
          </View>
        ) : null}

        <Text style={styles.label}>Name</Text>
        <TextInput
          value={name}
          onChangeText={setName}
          editable={!role.is_protected}
          style={styles.input}
        />

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
          disabled={updateMutation.isPending || !name.trim()}
          onPress={() => {
            void (async () => {
              try {
                await updateMutation.mutateAsync({
                  name: name.trim(),
                  description: description.trim() || null,
                  permissions,
                });
                router.back();
              } catch (error) {
                const message = error instanceof ApiError ? error.message : 'Could not update role.';
                Alert.alert('Update failed', message);
              }
            })();
          }}
          style={[styles.button, updateMutation.isPending ? styles.buttonDisabled : null]}>
          <Text style={styles.buttonText}>{updateMutation.isPending ? 'Saving…' : 'Save changes'}</Text>
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
  centered: {
    alignItems: 'center',
    backgroundColor: '#f8fafc',
    flex: 1,
    justifyContent: 'center',
  },
  error: {
    color: '#b91c1c',
    fontSize: 15,
  },
  notice: {
    backgroundColor: '#fef3c7',
    borderColor: '#fcd34d',
    borderRadius: 10,
    borderWidth: 1,
    marginBottom: 16,
    padding: 12,
  },
  noticeText: {
    color: '#92400e',
    fontSize: 14,
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
