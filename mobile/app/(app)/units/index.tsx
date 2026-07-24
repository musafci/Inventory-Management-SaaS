import { Stack } from 'expo-router';
import { Alert } from 'react-native';

import { EntityListCard, HeaderAction, PaginatedListScreen } from '@/components/ui';

import { useAuth } from '@/src/auth/AuthContext';
import { useDeleteUnit, useUnitsList } from '@/src/hooks/useCatalog';
import { canCreateInventory, canDeleteInventory, canUpdateInventory } from '@/src/permissions';

export default function UnitsScreen() {
  const { permissions } = useAuth();
  const query = useUnitsList();
  const deleteMutation = useDeleteUnit();

  const handleDelete = (id: number, name: string) => {
    Alert.alert('Delete unit', `Delete ${name}?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Delete',
        style: 'destructive',
        onPress: () => {
          void deleteMutation.mutateAsync(id);
        },
      },
    ]);
  };

  return (
    <>
      <Stack.Screen
        options={{
          title: 'Units',
          headerRight: () => (
            canCreateInventory(permissions) ? (
              <HeaderAction href="/(app)/units/new" label="Add" />
            ) : null
          ),
        }}
      />

      <PaginatedListScreen
        data={query.data ?? []}
        emptyMessage="No units yet."
        isLoading={query.isLoading}
        isRefetching={query.isRefetching}
        keyExtractor={(item) => String(item.id)}
        onRefresh={() => {
          void query.refetch();
        }}
        renderItem={(item) => (
          <EntityListCard
            canDelete={canDeleteInventory(permissions)}
            canEdit={canUpdateInventory(permissions)}
            editHref={`/(app)/units/${item.id}/edit`}
            onDelete={() => handleDelete(item.id, item.name)}
            subtitle={item.symbol}
            title={item.name}
          />
        )}
      />
    </>
  );
}
