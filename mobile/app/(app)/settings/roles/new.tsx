import { Stack, useRouter } from 'expo-router';
import { useState } from 'react';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';

import { Button, FormScreen, Input, LoadingState, SectionHeader } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import { useCreateRole, usePermissionGroups } from '@/src/hooks/useTeam';
import { theme } from '@/src/theme';

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

  const handleSubmit = () => {
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
  };

  if (groupsQuery.isLoading) {
    return <LoadingState />;
  }

  return (
    <>
      <Stack.Screen options={{ title: 'New role' }} />
      <FormScreen>
        <Input label="Name" value={name} onChangeText={setName} />
        <Input label="Description" multiline value={description} onChangeText={setDescription} />

        <SectionHeader title="Permissions" />
        {Object.entries(groups).map(([groupName, groupPermissions]) => (
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
        ))}

        <Button
          disabled={!name.trim()}
          label="Create role"
          loading={mutation.isPending}
          onPress={handleSubmit}
        />
      </FormScreen>
    </>
  );
}

const styles = StyleSheet.create({
  group: {
    marginBottom: theme.spacing.lg,
  },
  groupTitle: {
    ...theme.typography.label,
    color: theme.colors.textSecondary,
    marginBottom: theme.spacing.sm,
  },
  permissionRow: {
    alignItems: 'center',
    backgroundColor: theme.colors.surfaceMuted,
    borderColor: theme.colors.border,
    borderRadius: theme.radius.sm,
    borderWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: theme.spacing.sm,
    paddingHorizontal: theme.spacing.md,
    paddingVertical: 10,
  },
  permissionSelected: {
    backgroundColor: theme.colors.primarySoft,
    borderColor: theme.colors.primary,
  },
  permissionText: {
    color: theme.colors.text,
    flex: 1,
    fontSize: 14,
  },
  checkmark: {
    color: theme.colors.primary,
    fontSize: 16,
    fontWeight: '700',
    marginLeft: theme.spacing.sm,
  },
});
