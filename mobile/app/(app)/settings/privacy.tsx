import { Stack } from 'expo-router';
import { Alert, StyleSheet, Text } from 'react-native';

import {
  Button,
  Card,
  ErrorState,
  LoadingState,
  ScreenContainer,
  ScreenScrollView,
} from '@/components/ui';
import { ApiError } from '@/src/api/client';
import {
  useCancelOrganizationDeletion,
  useOrganization,
  useQueueOrganizationExport,
  useRequestOrganizationDeletion,
} from '@/src/hooks/useOrganization';
import { theme } from '@/src/theme';

export default function PrivacySettingsScreen() {
  const orgQuery = useOrganization();
  const exportMutation = useQueueOrganizationExport();
  const requestDeletionMutation = useRequestOrganizationDeletion();
  const cancelDeletionMutation = useCancelOrganizationDeletion();
  const org = orgQuery.data;

  const handleExport = () => {
    void (async () => {
      try {
        await exportMutation.mutateAsync();
        Alert.alert(
          'Export queued',
          'Your organization data export has been queued. You will receive an email when it is ready.',
        );
      } catch (error) {
        const message = error instanceof ApiError ? error.message : 'Could not queue export.';
        Alert.alert('Export failed', message);
      }
    })();
  };

  const handleRequestDeletion = () => {
    Alert.alert(
      'Request deletion',
      'This will schedule your organization for deletion after a grace period. Continue?',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Request deletion',
          style: 'destructive',
          onPress: () => {
            void (async () => {
              try {
                await requestDeletionMutation.mutateAsync();
                Alert.alert('Deletion requested', 'Your organization deletion has been scheduled.');
              } catch (error) {
                const message = error instanceof ApiError
                  ? error.message
                  : 'Could not request deletion.';
                Alert.alert('Request failed', message);
              }
            })();
          },
        },
      ],
    );
  };

  const handleCancelDeletion = () => {
    Alert.alert(
      'Cancel deletion',
      'Cancel the scheduled organization deletion?',
      [
        { text: 'No', style: 'cancel' },
        {
          text: 'Cancel deletion',
          onPress: () => {
            void (async () => {
              try {
                await cancelDeletionMutation.mutateAsync();
                Alert.alert('Deletion cancelled', 'Your organization deletion has been cancelled.');
              } catch (error) {
                const message = error instanceof ApiError
                  ? error.message
                  : 'Could not cancel deletion.';
                Alert.alert('Cancel failed', message);
              }
            })();
          },
        },
      ],
    );
  };

  return (
    <>
      <Stack.Screen options={{ title: 'Privacy & data' }} />
      {orgQuery.isLoading ? (
        <ScreenContainer><LoadingState /></ScreenContainer>
      ) : org ? (
        <ScreenScrollView>
          <Card>
            <Text style={styles.cardTitle}>Data export</Text>
            <Text style={styles.cardBody}>
              Request a full export of your organization data. You will receive an email when the
              export is ready to download.
            </Text>
            <Button
              disabled={exportMutation.isPending}
              label={exportMutation.isPending ? 'Queuing…' : 'Queue data export'}
              loading={exportMutation.isPending}
              onPress={handleExport}
              style={styles.action}
            />
          </Card>

          <Card>
            <Text style={styles.cardTitle}>Account deletion</Text>
            {org.deletion_requested_at ? (
              <>
                <Text style={styles.warning}>
                  Deletion requested on {org.deletion_requested_at}
                </Text>
                {org.deletion_scheduled_for ? (
                  <Text style={styles.cardBody}>
                    Scheduled for: {org.deletion_scheduled_for}
                  </Text>
                ) : null}
                <Button
                  disabled={cancelDeletionMutation.isPending}
                  label={cancelDeletionMutation.isPending ? 'Cancelling…' : 'Cancel deletion'}
                  loading={cancelDeletionMutation.isPending}
                  variant="secondary"
                  onPress={handleCancelDeletion}
                  style={styles.action}
                />
              </>
            ) : (
              <>
                <Text style={styles.cardBody}>
                  Request permanent deletion of your organization and all associated data after a
                  grace period.
                </Text>
                <Button
                  disabled={requestDeletionMutation.isPending}
                  label={requestDeletionMutation.isPending ? 'Requesting…' : 'Request deletion'}
                  loading={requestDeletionMutation.isPending}
                  variant="danger"
                  onPress={handleRequestDeletion}
                  style={styles.action}
                />
              </>
            )}
          </Card>
        </ScreenScrollView>
      ) : (
        <ScreenContainer><ErrorState message="Could not load organization." /></ScreenContainer>
      )}
    </>
  );
}

const styles = StyleSheet.create({
  cardTitle: {
    ...theme.typography.heading,
    color: theme.colors.text,
  },
  cardBody: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
    lineHeight: 20,
    marginTop: theme.spacing.sm,
  },
  warning: {
    color: theme.colors.warning,
    fontSize: 14,
    fontWeight: '600',
    marginTop: theme.spacing.sm,
  },
  action: {
    marginTop: theme.spacing.lg,
  },
});
