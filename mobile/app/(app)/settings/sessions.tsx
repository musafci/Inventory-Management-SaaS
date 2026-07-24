import { Stack } from 'expo-router';
import { Alert, StyleSheet, Text } from 'react-native';

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

  return (
    <>
      <Stack.Screen options={{ title: 'Active sessions' }} />
      <Text style={styles.description}>
        Devices and apps where your account is signed in. Revoke any session you do not recognize.
      </Text>

      {query.isLoading ? (
        <ScreenContainer><LoadingState /></ScreenContainer>
      ) : (
        <PaginatedListScreen
          data={query.data ?? []}
          emptyMessage="No active sessions found."
          isLoading={false}
          isRefetching={query.isRefetching}
          keyExtractor={(item) => item.id}
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
  description: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    lineHeight: 20,
    paddingHorizontal: theme.spacing.xl,
    paddingTop: theme.spacing.lg,
  },
});
