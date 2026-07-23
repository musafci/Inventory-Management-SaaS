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
        <View style={styles.card}>
          <Text style={styles.label}>Settings</Text>
          <Text style={styles.meta}>Organization, billing, team, and roles arrive in Phase 3.</Text>
        </View>
      ) : null}

      <Pressable onPress={() => logout()} style={styles.logoutButton}>
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
  meta: {
    color: '#64748b',
    fontSize: 14,
    marginTop: 8,
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
