import { Link } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useAuth } from '@/src/auth/AuthContext';
import { canAccessSettings } from '@/src/permissions';

export default function MoreScreen() {
  const { logout, permissions, user } = useAuth();

  return (
    <View style={styles.container}>
      <View style={styles.card}>
        <Text style={styles.label}>Signed in as</Text>
        <Text style={styles.value}>{user?.email}</Text>
      </View>

      {canAccessSettings(permissions) ? (
        <Link href="/(app)/settings" asChild>
          <Pressable
            accessibilityLabel="Settings"
            accessibilityRole="button"
            style={styles.linkCard}
            testID="hub-settings">
            <Text style={styles.linkTitle}>Settings</Text>
            <Text style={styles.meta}>Organization, billing, team, and roles.</Text>
          </Pressable>
        </Link>
      ) : null}

      <Link href="/(app)/settings/sync" asChild>
        <Pressable
          accessibilityLabel="Sync status"
          accessibilityRole="button"
          style={styles.linkCard}
          testID="hub-sync-status">
          <Text style={styles.linkTitle}>Sync status</Text>
          <Text style={styles.meta}>View pending changes and sync now.</Text>
        </Pressable>
      </Link>

      <Pressable
        accessibilityLabel="Sign out"
        accessibilityRole="button"
        onPress={() => logout()}
        style={styles.logoutButton}>
        <Text style={styles.logoutText}>Sign out</Text>
      </Pressable>
    </View>
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
    fontSize: 16,
    fontWeight: '600',
    marginTop: 8,
  },
  linkCard: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 12,
    padding: 16,
  },
  linkTitle: {
    color: '#0f172a',
    fontSize: 17,
    fontWeight: '700',
  },
  meta: {
    color: '#64748b',
    fontSize: 14,
    marginTop: 6,
  },
  logoutButton: {
    alignItems: 'center',
    backgroundColor: '#fee2e2',
    borderRadius: 12,
    marginTop: 8,
    paddingVertical: 14,
  },
  logoutText: {
    color: '#b91c1c',
    fontSize: 16,
    fontWeight: '600',
  },
});
