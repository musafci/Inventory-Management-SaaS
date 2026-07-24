import { Stack } from 'expo-router';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';

import { OptimizedFlatList } from '@/components/OptimizedFlatList';
import { useSync } from '@/src/sync/SyncContext';

function formatSyncedAt(value: string | null): string {
  if (!value) {
    return 'Never';
  }

  return new Date(value).toLocaleString();
}

export default function SyncSettingsScreen() {
  const {
    isReady,
    isSyncing,
    pendingOutboxCount,
    failedMutations,
    lastSyncedAt,
    syncNow,
    retryMutation,
    dismissMutation,
  } = useSync();

  return (
    <>
      <Stack.Screen options={{ title: 'Sync status' }} />
      <View style={styles.container}>
        <View style={styles.card}>
          <Text style={styles.label}>Database</Text>
          <Text style={styles.value}>{isReady ? 'Ready' : 'Initializing…'}</Text>
        </View>

        <View style={styles.card}>
          <Text style={styles.label}>Pending changes</Text>
          <Text style={styles.value}>{pendingOutboxCount}</Text>
          <Text style={styles.meta}>Mutations waiting to sync to the server.</Text>
        </View>

        <View style={styles.card}>
          <Text style={styles.label}>Last synced</Text>
          <Text style={styles.value}>{formatSyncedAt(lastSyncedAt)}</Text>
        </View>

        <Pressable
          disabled={!isReady || isSyncing}
          onPress={() => {
            void syncNow();
          }}
          style={[styles.button, !isReady || isSyncing ? styles.buttonDisabled : null]}>
          <Text style={styles.buttonText}>
            {isSyncing ? 'Syncing…' : 'Sync now'}
          </Text>
        </Pressable>

        {failedMutations.length > 0 ? (
          <View style={styles.failedSection}>
            <Text style={styles.failedTitle}>Failed changes</Text>
            <Text style={styles.failedDescription}>
              These queued actions could not be applied. Retry after fixing the issue, or dismiss to remove them.
            </Text>
            <OptimizedFlatList
              data={failedMutations}
              keyExtractor={(item) => String(item.id)}
              scrollEnabled={false}
              renderItem={({ item }) => (
                <View style={styles.failedCard}>
                  <Text style={styles.failedMethod}>{item.method} {item.path}</Text>
                  <Text style={styles.failedError}>{item.error_message ?? 'Unknown error'}</Text>
                  <View style={styles.failedActions}>
                    <Pressable
                      onPress={() => {
                        void retryMutation(item.id);
                      }}
                      style={styles.retryButton}>
                      <Text style={styles.retryText}>Retry</Text>
                    </Pressable>
                    <Pressable
                      onPress={() => {
                        Alert.alert('Dismiss change', 'Remove this failed mutation from the queue?', [
                          { text: 'Cancel', style: 'cancel' },
                          {
                            text: 'Dismiss',
                            style: 'destructive',
                            onPress: () => {
                              void dismissMutation(item.id);
                            },
                          },
                        ]);
                      }}
                      style={styles.dismissButton}>
                      <Text style={styles.dismissText}>Dismiss</Text>
                    </Pressable>
                  </View>
                </View>
              )}
            />
          </View>
        ) : null}
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f8fafc',
    flex: 1,
    padding: 20,
  },
  card: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 16,
    borderWidth: 1,
    marginBottom: 12,
    padding: 16,
  },
  label: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '600',
    textTransform: 'uppercase',
  },
  value: {
    color: '#0f172a',
    fontSize: 20,
    fontWeight: '700',
    marginTop: 8,
  },
  meta: {
    color: '#64748b',
    fontSize: 13,
    marginTop: 6,
  },
  button: {
    alignItems: 'center',
    backgroundColor: '#2563eb',
    borderRadius: 12,
    marginTop: 8,
    paddingVertical: 14,
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '700',
  },
  failedSection: {
    marginTop: 20,
  },
  failedTitle: {
    color: '#0f172a',
    fontSize: 18,
    fontWeight: '700',
  },
  failedDescription: {
    color: '#64748b',
    fontSize: 14,
    lineHeight: 20,
    marginBottom: 12,
    marginTop: 6,
  },
  failedCard: {
    backgroundColor: '#fff',
    borderColor: '#fecaca',
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 10,
    padding: 14,
  },
  failedMethod: {
    color: '#0f172a',
    fontFamily: 'SpaceMono',
    fontSize: 12,
  },
  failedError: {
    color: '#b91c1c',
    fontSize: 14,
    lineHeight: 20,
    marginTop: 8,
  },
  failedActions: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 12,
  },
  retryButton: {
    backgroundColor: '#dbeafe',
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  retryText: {
    color: '#1d4ed8',
    fontSize: 14,
    fontWeight: '600',
  },
  dismissButton: {
    backgroundColor: '#fee2e2',
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  dismissText: {
    color: '#b91c1c',
    fontSize: 14,
    fontWeight: '600',
  },
});
