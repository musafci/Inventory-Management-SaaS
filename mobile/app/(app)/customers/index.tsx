import { Stack } from 'expo-router';
import { Alert } from 'react-native';

import { EntityListCard, HeaderAction, PaginatedListScreen } from '@/components/ui';

import { useAuth } from '@/src/auth/AuthContext';
import { useCustomers, useCustomersList, useDeleteCustomer } from '@/src/hooks/usePartners';
import { canCreateCustomer, canDeleteCustomer, canUpdateCustomer } from '@/src/permissions';

export default function CustomersScreen() {
  const { permissions } = useAuth();
  const query = useCustomers('');
  const customers = useCustomersList('');
  const deleteMutation = useDeleteCustomer();

  const handleDelete = (id: number, name: string) => {
    Alert.alert('Delete customer', `Delete ${name}?`, [
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
          title: 'Customers',
          headerRight: () => (
            canCreateCustomer(permissions) ? (
              <HeaderAction href="/(app)/customers/new" label="Add" />
            ) : null
          ),
        }}
      />

      <PaginatedListScreen
        data={customers}
        emptyMessage="No customers yet."
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
            canDelete={canDeleteCustomer(permissions)}
            canEdit={canUpdateCustomer(permissions)}
            editHref={`/(app)/customers/${item.id}/edit`}
            onDelete={() => handleDelete(item.id, item.name)}
            subtitle={item.email ?? item.phone ?? 'No contact info'}
            title={item.name}
          />
        )}
      />
    </>
  );
}
