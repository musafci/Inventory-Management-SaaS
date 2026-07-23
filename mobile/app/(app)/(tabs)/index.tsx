import { StyleSheet, Text, View } from 'react-native';

import { useAuth } from '@/src/auth/AuthContext';

export default function HomeScreen() {
  const { user, organizationId, organizations, permissions } = useAuth();
  const organization = organizations.find((item) => item.id === organizationId);

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Dashboard</Text>
      <Text style={styles.subtitle}>
        Welcome back, {user?.name ?? 'User'}
      </Text>
      <View style={styles.card}>
        <Text style={styles.cardLabel}>Organization</Text>
        <Text style={styles.cardValue}>{organization?.name ?? '—'}</Text>
        <Text style={styles.cardMeta}>{organization?.plan ?? '—'} · {organization?.status ?? '—'}</Text>
      </View>
      <View style={styles.card}>
        <Text style={styles.cardLabel}>Permissions loaded</Text>
        <Text style={styles.cardValue}>{permissions.length}</Text>
        <Text style={styles.cardMeta}>Phase 0 foundation is ready for feature screens.</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f8fafc',
    flex: 1,
    padding: 20,
  },
  title: {
    color: '#0f172a',
    fontSize: 28,
    fontWeight: '700',
  },
  subtitle: {
    color: '#64748b',
    fontSize: 15,
    marginBottom: 20,
    marginTop: 6,
  },
  card: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 16,
    borderWidth: 1,
    marginBottom: 12,
    padding: 16,
  },
  cardLabel: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '600',
    textTransform: 'uppercase',
  },
  cardValue: {
    color: '#0f172a',
    fontSize: 20,
    fontWeight: '700',
    marginTop: 8,
  },
  cardMeta: {
    color: '#64748b',
    fontSize: 13,
    marginTop: 6,
  },
});
