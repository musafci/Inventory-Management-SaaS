import { Stack } from 'expo-router';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { ApiError } from '@/src/api/client';
import {
  useCancelOrganizationDeletion,
  useOrganization,
  useQueueOrganizationExport,
  useRequestOrganizationDeletion,
} from '@/src/hooks/useOrganization';

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
      <ScrollView contentContainerStyle={styles.container}>
        {orgQuery.isLoading ? (
          <ActivityIndicator size="large" style={styles.loader} />
        ) : org ? (
          <>
            <View style={styles.card}>
              <Text style={styles.cardTitle}>Data export</Text>
              <Text style={styles.cardBody}>
                Request a full export of your organization data. You will receive an email when the
                export is ready to download.
              </Text>
              <Pressable
                disabled={exportMutation.isPending}
                onPress={handleExport}
                style={styles.button}>
                <Text style={styles.buttonText}>
                  {exportMutation.isPending ? 'Queuing…' : 'Queue data export'}
                </Text>
              </Pressable>
            </View>

            <View style={styles.card}>
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
                  <Pressable
                    disabled={cancelDeletionMutation.isPending}
                    onPress={handleCancelDeletion}
                    style={[styles.button, styles.secondaryButton]}>
                    <Text style={styles.secondaryButtonText}>
                      {cancelDeletionMutation.isPending ? 'Cancelling…' : 'Cancel deletion'}
                    </Text>
                  </Pressable>
                </>
              ) : (
                <>
                  <Text style={styles.cardBody}>
                    Request permanent deletion of your organization and all associated data after a
                    grace period.
                  </Text>
                  <Pressable
                    disabled={requestDeletionMutation.isPending}
                    onPress={handleRequestDeletion}
                    style={[styles.button, styles.dangerButton]}>
                    <Text style={styles.buttonText}>
                      {requestDeletionMutation.isPending ? 'Requesting…' : 'Request deletion'}
                    </Text>
                  </Pressable>
                </>
              )}
            </View>
          </>
        ) : (
          <Text style={styles.error}>Could not load organization.</Text>
        )}
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f8fafc',
    flexGrow: 1,
    padding: 16,
    paddingBottom: 40,
  },
  loader: {
    marginTop: 32,
  },
  error: {
    color: '#b91c1c',
    fontSize: 15,
    padding: 16,
  },
  card: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 16,
    padding: 16,
  },
  cardTitle: {
    color: '#0f172a',
    fontSize: 17,
    fontWeight: '700',
  },
  cardBody: {
    color: '#64748b',
    fontSize: 14,
    lineHeight: 20,
    marginTop: 8,
  },
  warning: {
    color: '#b45309',
    fontSize: 14,
    fontWeight: '600',
    marginTop: 8,
  },
  button: {
    alignItems: 'center',
    backgroundColor: '#2563eb',
    borderRadius: 10,
    marginTop: 16,
    paddingVertical: 12,
  },
  secondaryButton: {
    backgroundColor: '#fff',
    borderColor: '#2563eb',
    borderWidth: 1,
  },
  dangerButton: {
    backgroundColor: '#b91c1c',
  },
  buttonText: {
    color: '#fff',
    fontSize: 15,
    fontWeight: '700',
  },
  secondaryButtonText: {
    color: '#2563eb',
    fontSize: 15,
    fontWeight: '700',
  },
});
