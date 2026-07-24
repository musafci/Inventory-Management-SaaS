import { Link, Stack } from 'expo-router';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';

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
            <Link href="/(app)/settings/team/new" style={styles.headerLink}>
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
            <Text style={styles.error}>Could not load team members.</Text>
          </View>
        ) : (
          <FlatList
            data={members}
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
                <Text style={styles.empty}>No team members yet.</Text>
              </View>
            )}
            renderItem={({ item }) => (
              <View style={styles.row}>
                <View style={styles.rowBody}>
                  <Text style={styles.name}>{item.name}</Text>
                  <Text style={styles.meta}>{item.email}</Text>
                  <Text style={styles.meta}>
                    {item.role ?? 'No role'} · {item.status}
                  </Text>
                </View>
                <Pressable onPress={() => handleDelete(item.id, item.name)}>
                  <Text style={styles.deleteLink}>Remove</Text>
                </Pressable>
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
  deleteLink: {
    color: '#b91c1c',
    fontSize: 14,
    fontWeight: '600',
  },
});
