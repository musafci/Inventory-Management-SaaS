import { Stack } from 'expo-router';
import { Alert } from 'react-native';

import { EntityListCard, ErrorState, HeaderAction, PaginatedListScreen } from '@/components/ui';

import { ApiError } from '@/src/api/client';
import { useDeleteTeamMember, useTeamMembers } from '@/src/hooks/useTeam';

export default function TeamSettingsScreen() {
  const query = useTeamMembers();
  const deleteMutation = useDeleteTeamMember();
  const members = query.data ?? [];

  const handleDelete = (id: number, name: string) => {
    Alert.alert('Remove team member', `Remove ${name} from the organization?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Remove',
        style: 'destructive',
        onPress: () => {
          void (async () => {
            try {
              await deleteMutation.mutateAsync(id);
            } catch (error) {
              const message = error instanceof ApiError ? error.message : 'Could not remove member.';
              Alert.alert('Remove failed', message);
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
          title: 'Team',
          headerRight: () => (
            <HeaderAction href="/(app)/settings/team/new" label="Add" />
          ),
        }}
      />

      {query.isError ? (
        <ErrorState message="Could not load team members." />
      ) : (
        <PaginatedListScreen
          data={members}
          emptyMessage="No team members yet."
          isLoading={query.isLoading}
          isRefetching={query.isRefetching}
          keyExtractor={(item) => String(item.id)}
          onRefresh={() => {
            void query.refetch();
          }}
          renderItem={(item) => (
            <EntityListCard
              canDelete
              canEdit
              editHref={`/(app)/settings/team/${item.id}/edit`}
              onDelete={() => handleDelete(item.id, item.name)}
              subtitle={`${item.email}\n${item.role ?? 'No role'} · ${item.status}`}
              title={item.name}
            />
          )}
        />
      )}
    </>
  );
}
