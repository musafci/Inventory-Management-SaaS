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
            <Link href="/(app)/settings/roles/new" style={styles.headerLink}>
              Add
            </Link>
          ),
        }}
      />

      <View style={styles.container}>
        {query.isLoading ? (
          <View style={styles.centered}>
            <ActivityIndicator size="large" />
          </View>
        ) : query.isError ? (
          <View style={styles.centered}>
            <Text style={styles.error}>Could not load roles.</Text>
          </View>
        ) : (
          <OptimizedFlatList
            data={roles}
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
                <Text style={styles.empty}>No roles yet.</Text>
              </View>
            )}
            renderItem={({ item }) => (
              <View style={styles.row}>
                <View style={styles.rowBody}>
                  <Text style={styles.name}>{item.name}</Text>
                  {item.description ? (
                    <Text style={styles.meta}>{item.description}</Text>
                  ) : null}
                  <Text style={styles.meta}>
                    {item.permissions?.length ?? 0} permissions
                    {item.users_count !== undefined ? ` · ${item.users_count} users` : ''}
                  </Text>
                </View>
                <View style={styles.actions}>
                  <Link href={`/(app)/settings/roles/${item.id}/edit`} style={styles.actionLink}>
                    Edit
                  </Link>
                  {!item.is_protected ? (
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
  error: {
    color: '#b91c1c',
    fontSize: 15,
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
});
