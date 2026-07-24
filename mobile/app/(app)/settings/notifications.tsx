import { Stack } from 'expo-router';
import { Alert, StyleSheet, Switch, Text, View } from 'react-native';

import {
  Card,
  LoadingState,
  ScreenScrollView,
  SectionHeader,
} from '@/components/ui';
import { ApiError } from '@/src/api/client';
import {
  useNotificationPreferences,
  useUpdateNotificationPreferences,
} from '@/src/hooks/useNotifications';
import { theme } from '@/src/theme';

const EVENT_LABELS: Record<string, string> = {
  low_stock: 'Low stock alerts',
  sales_order_status: 'Sales order updates',
  purchase_order_status: 'Purchase order updates',
  trial_ending: 'Trial ending reminders',
  payment_past_due: 'Payment past due alerts',
};

export default function NotificationSettingsScreen() {
  const query = useNotificationPreferences();
  const updateMutation = useUpdateNotificationPreferences();

  const togglePreference = async (eventKey: string, enabled: boolean) => {
    const current = query.data?.preferences ?? {};

    try {
      await updateMutation.mutateAsync({
        ...current,
        [eventKey]: enabled,
      });
    } catch (error) {
      const message = error instanceof ApiError ? error.message : 'Could not update preference.';
      Alert.alert('Update failed', message);
    }
  };

  return (
    <>
      <Stack.Screen options={{ title: 'Notifications' }} />
      <ScreenScrollView>
        <SectionHeader title="Notification preferences" />
        <Text style={styles.description}>
          Choose which push notifications you receive for this organization.
        </Text>

        {query.isLoading ? (
          <LoadingState />
        ) : (
          (query.data?.events ?? []).map((eventKey) => (
            <Card key={eventKey} style={styles.row}>
              <View style={styles.rowBody}>
                <Text style={styles.rowTitle}>{EVENT_LABELS[eventKey] ?? eventKey}</Text>
              </View>
              <Switch
                disabled={updateMutation.isPending}
                onValueChange={(value) => {
                  void togglePreference(eventKey, value);
                }}
                thumbColor={theme.colors.surface}
                trackColor={{ false: theme.colors.border, true: theme.colors.primary }}
                value={query.data?.preferences[eventKey] ?? true}
              />
            </Card>
          ))
        )}
      </ScreenScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  description: {
    ...theme.typography.body,
    color: theme.colors.textSecondary,
    marginBottom: theme.spacing.lg,
  },
  row: {
    alignItems: 'center',
    flexDirection: 'row',
    marginBottom: theme.spacing.sm,
    paddingVertical: theme.spacing.md,
  },
  rowBody: {
    flex: 1,
    paddingRight: theme.spacing.md,
  },
  rowTitle: {
    ...theme.typography.bodyStrong,
    color: theme.colors.text,
  },
});
