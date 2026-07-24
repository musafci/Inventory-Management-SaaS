import { Stack } from 'expo-router';
import { Alert } from 'react-native';

import { EntityListCard, HeaderAction, PaginatedListScreen } from '@/components/ui';

import { useAuth } from '@/src/auth/AuthContext';
import { useDeleteSupplier, useSuppliers, useSuppliersList } from '@/src/hooks/usePartners';
import { canCreateSupplier, canDeleteSupplier, canUpdateSupplier } from '@/src/permissions';

export default function SuppliersScreen() {
  const { permissions } = useAuth();
  const query = useSuppliers('');
  const suppliers = useSuppliersList('');
  const deleteMutation = useDeleteSupplier();

  const handleDelete = (id: number, name: string) => {
    Alert.alert('Delete supplier', `Delete ${name}?`, [
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
          title: 'Suppliers',
          headerRight: () => (
            canCreateSupplier(permissions) ? (
              <HeaderAction href="/(app)/suppliers/new" label="Add" />
            ) : null
          ),
        }}
      />

      <PaginatedListScreen
        data={suppliers}
        emptyMessage="No suppliers yet."
        hasNextPage={query.hasNextPage}
        isFetchingNextPage={query.isFetchingNextPage}
        isLoading={query.isLoading}
        isRefetching={query.isRefetching}
        keyExtractor={(item) => String(item.id)}
        onEndReached={() => {
          void query.fetchNextPage();
        }}
        onRefresh={() => {
          void query.refetch();
        }}
        renderItem={(item) => (
          <EntityListCard
            canDelete={canDeleteSupplier(permissions)}
            canEdit={canUpdateSupplier(permissions)}
            editHref={`/(app)/suppliers/${item.id}/edit`}
            onDelete={() => handleDelete(item.id, item.name)}
            subtitle={item.email ?? item.phone ?? item.contact_person ?? 'No contact info'}
            title={item.name}
          />
        )}
      />
    </>
  );
}
