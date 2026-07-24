import { Stack } from 'expo-router';
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

import { useRevokeSession, useSessions } from '@/src/hooks/useSessions';

function formatDate(value: string | null): string {
  if (!value) {
    return 'Unknown';
  }

  return new Date(value).toLocaleString();
}

export default function SessionsScreen() {
  const query = useSessions();
  const revokeMutation = useRevokeSession();

  const handleRevoke = (tokenId: string, isCurrent: boolean) => {
    const message = isCurrent
      ? 'This will sign you out on this device.'
      : 'Revoke this session?';

    Alert.alert('Revoke session', message, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Revoke',
        style: 'destructive',
        onPress: () => {
          void revokeMutation.mutateAsync(tokenId);
        },
      },
    ]);
  };

  return (
    <>
      <Stack.Screen options={{ title: 'Active sessions' }} />
      <View style={styles.container}>
        <Text style={styles.description}>
          Devices and apps where your account is signed in. Revoke any session you do not recognize.
        </Text>

        {query.isLoading ? (
          <View style={styles.centered}>
            <ActivityIndicator size="large" />
          </View>
        ) : (
          <OptimizedFlatList
            data={query.data ?? []}
            keyExtractor={(item) => item.id}
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
                <Text style={styles.empty}>No active sessions found.</Text>
              </View>
            )}
            renderItem={({ item }) => (
              <View style={styles.row}>
                <View style={styles.rowBody}>
                  <Text style={styles.name}>
                    {item.name ?? 'Mobile app'}
                    {item.is_current ? ' (this device)' : ''}
                  </Text>
                  <Text style={styles.meta}>Signed in: {formatDate(item.created_at)}</Text>
                  {item.expires_at ? (
                    <Text style={styles.meta}>Expires: {formatDate(item.expires_at)}</Text>
                  ) : null}
                </View>
                <Pressable
                  disabled={revokeMutation.isPending}
                  onPress={() => handleRevoke(item.id, item.is_current)}>
                  <Text style={styles.revokeLink}>Revoke</Text>
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
  description: {
    color: '#64748b',
    fontSize: 14,
    lineHeight: 20,
    paddingHorizontal: 16,
    paddingTop: 16,
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
  revokeLink: {
    color: '#b91c1c',
    fontSize: 14,
    fontWeight: '600',
  },
});
