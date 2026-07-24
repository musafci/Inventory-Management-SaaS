import { Stack } from 'expo-router';
import { Alert, StyleSheet, Text, View } from 'react-native';

import {
  ListRow,
  LoadingState,
  PaginatedListScreen,
  ScreenContainer,
  TextAction,
} from '@/components/ui';
import { useRevokeSession, useSessions } from '@/src/hooks/useSessions';
import { theme } from '@/src/theme';

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

  const listHeader = (
    <View style={styles.descriptionWrap}>
      <Text style={styles.description}>
        Devices and apps where your account is signed in. Revoke any session you do not recognize.
      </Text>
    </View>
  );

  return (
    <>
      <Stack.Screen options={{ title: 'Active sessions' }} />

      {query.isLoading ? (
        <ScreenContainer><LoadingState /></ScreenContainer>
      ) : (
        <PaginatedListScreen
          data={query.data ?? []}
          emptyMessage="No active sessions found."
          isLoading={false}
          isRefetching={query.isRefetching}
          keyExtractor={(item) => item.id}
          ListHeaderComponent={listHeader}
          onRefresh={() => {
            void query.refetch();
          }}
          renderItem={(item) => (
            <ListRow
              right={(
                <TextAction
                  label="Revoke"
                  tone="danger"
                  onPress={() => handleRevoke(item.id, item.is_current)}
                />
              )}
              showChevron={false}
              subtitle={`Signed in: ${formatDate(item.created_at)}${
                item.expires_at ? ` · Expires: ${formatDate(item.expires_at)}` : ''
              }`}
              title={`${item.name ?? 'Mobile app'}${item.is_current ? ' (this device)' : ''}`}
            />
          )}
        />
      )}
    </>
  );
}

const styles = StyleSheet.create({
  descriptionWrap: {
    paddingBottom: theme.spacing.sm,
    paddingHorizontal: theme.spacing.lg,
    paddingTop: theme.spacing.md,
  },
  description: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    lineHeight: 20,
  },
});
