import { Stack } from 'expo-router';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  View,
} from 'react-native';

import { ApiError } from '@/src/api/client';
import {
  useNotificationPreferences,
  useUpdateNotificationPreferences,
} from '@/src/hooks/useNotifications';

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
      <ScrollView contentContainerStyle={styles.container}>
        <Text style={styles.title}>Notification preferences</Text>
        <Text style={styles.description}>
          Choose which push notifications you receive for this organization.
        </Text>

        {query.isLoading ? (
          <ActivityIndicator size="large" />
        ) : (
          (query.data?.events ?? []).map((eventKey) => (
            <View key={eventKey} style={styles.row}>
              <View style={styles.rowBody}>
                <Text style={styles.rowTitle}>{EVENT_LABELS[eventKey] ?? eventKey}</Text>
              </View>
              <Switch
                disabled={updateMutation.isPending}
                onValueChange={(value) => {
                  void togglePreference(eventKey, value);
                }}
                value={query.data?.preferences[eventKey] ?? true}
              />
            </View>
          ))
        )}
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f8fafc',
    flexGrow: 1,
    padding: 20,
  },
  title: {
    color: '#0f172a',
    fontSize: 24,
    fontWeight: '700',
  },
  description: {
    color: '#64748b',
    fontSize: 15,
    lineHeight: 22,
    marginBottom: 16,
    marginTop: 8,
  },
  row: {
    alignItems: 'center',
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 12,
    borderWidth: 1,
    flexDirection: 'row',
    marginBottom: 10,
    padding: 16,
  },
  rowBody: {
    flex: 1,
    paddingRight: 12,
  },
  rowTitle: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '600',
  },
});
