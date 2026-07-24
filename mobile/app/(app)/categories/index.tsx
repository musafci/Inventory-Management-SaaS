import { Stack } from 'expo-router';
import { Alert } from 'react-native';

import { EntityListCard, HeaderAction, PaginatedListScreen } from '@/components/ui';

import { useAuth } from '@/src/auth/AuthContext';
import { useCategoriesList, useDeleteCategory } from '@/src/hooks/useCatalog';
import { canCreateInventory, canDeleteInventory, canUpdateInventory } from '@/src/permissions';

export default function CategoriesScreen() {
  const { permissions } = useAuth();
  const query = useCategoriesList();
  const deleteMutation = useDeleteCategory();

  const handleDelete = (id: number, name: string) => {
    Alert.alert('Delete category', `Delete ${name}?`, [
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
          title: 'Categories',
          headerRight: () => (
            canCreateInventory(permissions) ? (
              <HeaderAction href="/(app)/categories/new" label="Add" />
            ) : null
          ),
        }}
      />

      <PaginatedListScreen
        data={query.data ?? []}
        emptyMessage="No categories yet."
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
            editHref={`/(app)/categories/${item.id}/edit`}
            onDelete={() => handleDelete(item.id, item.name)}
            subtitle={item.slug}
            title={item.name}
          />
        )}
      />
    </>
  );
}
