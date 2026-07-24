import { Stack } from 'expo-router';
import { Alert, StyleSheet, Text, View } from 'react-native';

import {
  Button,
  Card,
  DetailRow,
  ScreenScrollView,
  SectionHeader,
} from '@/components/ui';
import { OptimizedFlatList } from '@/components/OptimizedFlatList';
import { useSync } from '@/src/sync/SyncContext';
import { theme } from '@/src/theme';

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
      <ScreenScrollView>
        <Card>
          <DetailRow label="Database" value={isReady ? 'Ready' : 'Initializing…'} />
        </Card>

        <Card>
          <DetailRow label="Pending changes" value={String(pendingOutboxCount)} />
          <Text style={styles.meta}>Mutations waiting to sync to the server.</Text>
        </Card>

        <Card>
          <DetailRow label="Last synced" value={formatSyncedAt(lastSyncedAt)} />
        </Card>

        <Button
          disabled={!isReady || isSyncing}
          label={isSyncing ? 'Syncing…' : 'Sync now'}
          loading={isSyncing}
          onPress={() => {
            void syncNow();
          }}
        />

        {failedMutations.length > 0 ? (
          <View style={styles.failedSection}>
            <SectionHeader title="Failed changes" />
            <Text style={styles.failedDescription}>
              These queued actions could not be applied. Retry after fixing the issue, or dismiss to remove them.
            </Text>
            <OptimizedFlatList
              data={failedMutations}
              keyExtractor={(item) => String(item.id)}
              scrollEnabled={false}
              renderItem={({ item }) => (
                <Card style={styles.failedCard}>
                  <Text style={styles.failedMethod}>{item.method} {item.path}</Text>
                  <Text style={styles.failedError}>{item.error_message ?? 'Unknown error'}</Text>
                  <View style={styles.failedActions}>
                    <Button
                      label="Retry"
                      variant="ghost"
                      onPress={() => {
                        void retryMutation(item.id);
                      }}
                    />
                    <Button
                      label="Dismiss"
                      variant="danger"
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
                    />
                  </View>
                </Card>
              )}
            />
          </View>
        ) : null}
      </ScreenScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  meta: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    marginTop: theme.spacing.sm,
  },
  failedSection: {
    marginTop: theme.spacing.xl,
  },
  failedDescription: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    lineHeight: 20,
    marginBottom: theme.spacing.md,
  },
  failedCard: {
    borderColor: theme.colors.danger,
    marginBottom: theme.spacing.sm,
  },
  failedMethod: {
    color: theme.colors.text,
    fontFamily: 'SpaceMono',
    fontSize: 12,
  },
  failedError: {
    color: theme.colors.danger,
    fontSize: 14,
    lineHeight: 20,
    marginTop: theme.spacing.sm,
  },
  failedActions: {
    flexDirection: 'row',
    gap: theme.spacing.sm,
    marginTop: theme.spacing.md,
  },
});
