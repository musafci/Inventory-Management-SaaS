import { Stack } from 'expo-router';
import { Alert } from 'react-native';

import { EntityListCard, HeaderAction, PaginatedListScreen } from '@/components/ui';

import { useAuth } from '@/src/auth/AuthContext';
import { useDeleteWarehouse, useWarehouses } from '@/src/hooks/useInventory';
import { canCreateInventory, canDeleteInventory, canUpdateInventory } from '@/src/permissions';

export default function WarehousesScreen() {
  const { permissions } = useAuth();
  const query = useWarehouses();
  const deleteMutation = useDeleteWarehouse();

  const handleDelete = (id: number, name: string) => {
    Alert.alert('Delete warehouse', `Delete ${name}?`, [
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
          title: 'Warehouses',
          headerRight: () => (
            canCreateInventory(permissions) ? (
              <HeaderAction href="/(app)/warehouses/new" label="Add" />
            ) : null
          ),
        }}
      />

      <PaginatedListScreen
        data={query.data ?? []}
        emptyMessage="No warehouses yet."
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
            editHref={`/(app)/warehouses/${item.id}/edit`}
            onDelete={() => handleDelete(item.id, item.name)}
            subtitle={item.address ?? undefined}
            title={`${item.name}${item.is_default ? ' (default)' : ''}`}
          />
        )}
      />
    </>
  );
}
