import { Stack } from 'expo-router';
import { Alert } from 'react-native';

import { EntityListCard, ErrorState, HeaderAction, PaginatedListScreen } from '@/components/ui';

import { ApiError } from '@/src/api/client';
import { useDeleteRole, useRoles } from '@/src/hooks/useTeam';

export default function RolesSettingsScreen() {
  const query = useRoles();
  const deleteMutation = useDeleteRole();
  const roles = query.data ?? [];

  const handleDelete = (id: number, name: string) => {
    Alert.alert('Delete role', `Delete role "${name}"?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Delete',
        style: 'destructive',
        onPress: () => {
          void (async () => {
            try {
              await deleteMutation.mutateAsync(id);
            } catch (error) {
              const message = error instanceof ApiError ? error.message : 'Could not delete role.';
              Alert.alert('Delete failed', message);
            }
          })();
        },
      },
    ]);
  };

  return (
    <>
      <Stack.Screen
        options={{
          title: 'Roles',
          headerRight: () => (
            <HeaderAction href="/(app)/settings/roles/new" label="Add" />
          ),
        }}
      />

      {query.isError ? (
        <ErrorState message="Could not load roles." />
      ) : (
        <PaginatedListScreen
          data={roles}
          emptyMessage="No roles yet."
          isLoading={query.isLoading}
          isRefetching={query.isRefetching}
          keyExtractor={(item) => String(item.id)}
          onRefresh={() => {
            void query.refetch();
          }}
          renderItem={(item) => {
            const metaParts = [
              `${item.permissions?.length ?? 0} permissions`,
              item.users_count !== undefined ? `${item.users_count} users` : null,
            ].filter(Boolean);

            const subtitle = [item.description, metaParts.join(' · ')].filter(Boolean).join('\n');

            return (
              <EntityListCard
                canDelete={!item.is_protected}
                canEdit
                editHref={`/(app)/settings/roles/${item.id}/edit`}
                onDelete={() => handleDelete(item.id, item.name)}
                subtitle={subtitle || undefined}
                title={item.name}
              />
            );
          }}
        />
      )}
    </>
  );
}
