import { StyleSheet, Text, View } from 'react-native';
import { SymbolView } from 'expo-symbols';

import { useNetwork } from '@/src/network/NetworkContext';
import { useSync } from '@/src/sync/SyncContext';
import { palette, theme } from '@/src/theme';

export function OfflineBanner() {
  const { isConnected } = useNetwork();
  const { pendingOutboxCount, isSyncing } = useSync();

  if (isConnected && pendingOutboxCount === 0 && !isSyncing) {
    return null;
  }

  const offline = !isConnected;
  const message = offline
    ? 'You are offline. Showing cached data where available.'
    : isSyncing
      ? 'Syncing changes…'
      : `${pendingOutboxCount} change${pendingOutboxCount === 1 ? '' : 's'} waiting to sync`;

  return (
    <View style={[styles.banner, offline ? styles.offline : styles.syncing]}>
      <SymbolView
        name={
          offline
            ? { ios: 'wifi.slash', android: 'wifi_off', web: 'wifi_off' }
            : { ios: 'arrow.triangle.2.circlepath', android: 'sync', web: 'sync' }
        }
        size={16}
        tintColor={offline ? palette.amber700 : theme.colors.info}
      />
      <Text style={styles.text}>{message}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  banner: {
    alignItems: 'center',
    borderBottomWidth: 1,
    flexDirection: 'row',
    gap: theme.spacing.sm,
    paddingHorizontal: theme.spacing.lg,
    paddingVertical: 10,
  },
  offline: {
    backgroundColor: palette.amber50,
    borderBottomColor: palette.amber200,
  },
  syncing: {
    backgroundColor: theme.colors.primarySoft,
    borderBottomColor: palette.primary200,
  },
  text: {
    color: theme.colors.text,
    flex: 1,
    fontSize: 13,
    fontWeight: '700',
  },
});
