import { Stack } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useSync } from '@/src/sync/SyncContext';

function formatSyncedAt(value: string | null): string {
  if (!value) {
    return 'Never';
  }

  return new Date(value).toLocaleString();
}

export default function SyncSettingsScreen() {
  const { isReady, isSyncing, pendingOutboxCount, lastSyncedAt, syncNow } = useSync();

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
});
