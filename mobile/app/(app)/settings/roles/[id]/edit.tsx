import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect, useMemo, useState } from 'react';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';

import { Button, ErrorState, FormScreen, Input, LoadingState, SectionHeader } from '@/components/ui';
import { ApiError } from '@/src/api/client';
import { usePermissionGroups, useRoles, useUpdateRole } from '@/src/hooks/useTeam';
import { theme } from '@/src/theme';

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

  const handleSubmit = () => {
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
  };

  if (rolesQuery.isLoading) {
    return (
      <>
        <Stack.Screen options={{ title: 'Edit role' }} />
        <LoadingState />
      </>
    );
  }

  if (!role) {
    return (
      <>
        <Stack.Screen options={{ title: 'Edit role' }} />
        <ErrorState message="Role not found." />
      </>
    );
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Edit role' }} />
      <FormScreen>
        {role.is_protected ? (
          <View style={styles.notice}>
            <Text style={styles.noticeText}>This is a protected role. Some fields may be restricted.</Text>
          </View>
        ) : null}

        <Input
          editable={!role.is_protected}
          label="Name"
          value={name}
          onChangeText={setName}
        />
        <Input label="Description" multiline value={description} onChangeText={setDescription} />

        <SectionHeader title="Permissions" />
        {groupsQuery.isLoading ? (
          <LoadingState />
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

        <Button
          disabled={!name.trim()}
          label="Save changes"
          loading={updateMutation.isPending}
          onPress={handleSubmit}
        />
      </FormScreen>
    </>
  );
}

const styles = StyleSheet.create({
  notice: {
    backgroundColor: theme.colors.warningSoft,
    borderColor: theme.colors.warning,
    borderRadius: theme.radius.sm,
    borderWidth: 1,
    marginBottom: theme.spacing.lg,
    padding: theme.spacing.md,
  },
  noticeText: {
    color: theme.colors.warning,
    fontSize: 14,
  },
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
