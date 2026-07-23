import { StyleSheet, Text, View } from 'react-native';

import { useNetwork } from '@/src/network/NetworkContext';
import { useSync } from '@/src/sync/SyncContext';

export function OfflineBanner() {
  const { isConnected } = useNetwork();
  const { pendingOutboxCount, isSyncing } = useSync();

  if (isConnected && pendingOutboxCount === 0 && !isSyncing) {
    return null;
  }

  const message = !isConnected
    ? 'You are offline. Showing cached data where available.'
    : isSyncing
      ? 'Syncing changes…'
      : `${pendingOutboxCount} change${pendingOutboxCount === 1 ? '' : 's'} waiting to sync`;

  return (
    <View style={[styles.banner, !isConnected ? styles.offline : styles.syncing]}>
      <Text style={styles.text}>{message}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  banner: {
    borderBottomWidth: 1,
    paddingHorizontal: 16,
    paddingVertical: 8,
  },
  offline: {
    backgroundColor: '#fef3c7',
    borderBottomColor: '#fcd34d',
  },
  syncing: {
    backgroundColor: '#dbeafe',
    borderBottomColor: '#93c5fd',
  },
  text: {
    color: '#1f2937',
    fontSize: 13,
    fontWeight: '600',
  },
});
