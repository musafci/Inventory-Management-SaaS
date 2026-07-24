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
              <Link href="/(app)/warehouses/new" style={styles.headerLink}>
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
            data={query.data ?? []}
            keyExtractor={(item) => String(item.id)}
            refreshControl={(
              <RefreshControl
                refreshing={query.isRefetching}
                onRefresh={() => {
                  void query.refetch();
                }}
              />
            )}
            ListEmptyComponent={(
              <View style={styles.centered}>
                <Text style={styles.empty}>No warehouses yet.</Text>
                {canCreateInventory(permissions) ? (
                  <Link href="/(app)/warehouses/new" style={styles.emptyLink}>
                    Create your first warehouse
                  </Link>
                ) : null}
              </View>
            )}
            renderItem={({ item }) => (
              <View style={styles.row}>
                <View style={styles.rowBody}>
                  <Text style={styles.name}>
                    {item.name}
                    {item.is_default ? ' (default)' : ''}
                  </Text>
                  {item.address ? <Text style={styles.meta}>{item.address}</Text> : null}
                </View>
                <View style={styles.actions}>
                  {canUpdateInventory(permissions) ? (
                    <Link href={`/(app)/warehouses/${item.id}/edit`} style={styles.actionLink}>
                      Edit
                    </Link>
                  ) : null}
                  {canDeleteInventory(permissions) ? (
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
  emptyLink: {
    color: '#2563eb',
    fontSize: 15,
    fontWeight: '600',
    marginTop: 12,
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
});
