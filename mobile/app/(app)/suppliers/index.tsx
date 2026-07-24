import { Link, Stack } from 'expo-router';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { OptimizedFlatList } from '@/components/OptimizedFlatList';

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
              <Link href="/(app)/suppliers/new" style={styles.headerLink}>
                Add
              </Link>
            ) : null
          ),
        }}
      />

      <View style={styles.container}>
        {query.isLoading ? (
          <View style={styles.centered}>
            <ActivityIndicator size="large" />
          </View>
        ) : (
          <OptimizedFlatList
            data={suppliers}
            keyExtractor={(item) => String(item.id)}
            refreshControl={(
              <RefreshControl
                refreshing={query.isRefetching}
                onRefresh={() => {
                  void query.refetch();
                }}
              />
            )}
            onEndReached={() => {
              if (query.hasNextPage && !query.isFetchingNextPage) {
                void query.fetchNextPage();
              }
            }}
            onEndReachedThreshold={0.4}
            ListEmptyComponent={(
              <View style={styles.centered}>
                <Text style={styles.empty}>No suppliers yet.</Text>
              </View>
            )}
            ListFooterComponent={
              query.isFetchingNextPage ? (
                <ActivityIndicator style={styles.footerLoader} />
              ) : null
            }
            renderItem={({ item }) => (
              <View style={styles.row}>
                <View style={styles.rowBody}>
                  <Text style={styles.name}>{item.name}</Text>
                  <Text style={styles.meta}>
                    {item.email ?? item.phone ?? item.contact_person ?? 'No contact info'}
                  </Text>
                </View>
                <View style={styles.actions}>
                  {canUpdateSupplier(permissions) ? (
                    <Link href={`/(app)/suppliers/${item.id}/edit`} style={styles.actionLink}>
                      Edit
                    </Link>
                  ) : null}
                  {canDeleteSupplier(permissions) ? (
                    <Pressable onPress={() => handleDelete(item.id, item.name)}>
                      <Text style={styles.deleteLink}>Delete</Text>
                    </Pressable>
                  ) : null}
                </View>
              </View>
            )}
          />
        )}
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f8fafc',
    flex: 1,
  },
  headerLink: {
    color: '#2563eb',
    fontSize: 16,
    fontWeight: '600',
    marginRight: 16,
  },
  centered: {
    alignItems: 'center',
    justifyContent: 'center',
    padding: 32,
  },
  empty: {
    color: '#64748b',
    fontSize: 15,
  },
  row: {
    alignItems: 'center',
    backgroundColor: '#fff',
    borderBottomColor: '#e2e8f0',
    borderBottomWidth: 1,
    flexDirection: 'row',
    paddingHorizontal: 16,
    paddingVertical: 14,
  },
  rowBody: {
    flex: 1,
  },
  name: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '600',
  },
  meta: {
    color: '#64748b',
    fontSize: 13,
    marginTop: 4,
  },
  actions: {
    alignItems: 'flex-end',
    gap: 8,
  },
  actionLink: {
    color: '#2563eb',
    fontSize: 14,
    fontWeight: '600',
  },
  deleteLink: {
    color: '#b91c1c',
    fontSize: 14,
    fontWeight: '600',
  },
  footerLoader: {
    marginVertical: 16,
  },
});
