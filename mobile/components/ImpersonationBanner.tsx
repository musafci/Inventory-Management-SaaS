import { StyleSheet, Text, View } from 'react-native';

import { useAuth } from '@/src/auth/AuthContext';

export function ImpersonationBanner() {
  const { impersonation, organizations, organizationId } = useAuth();

  if (!impersonation?.active) {
    return null;
  }

  const organizationName = organizations.find((org) => org.id === organizationId)?.name ?? 'tenant';

  return (
    <View style={styles.banner}>
      <Text style={styles.title}>Support impersonation active</Text>
      <Text style={styles.body}>
        Viewing {organizationName}
        {impersonation.platform_admin_name ? ` · ${impersonation.platform_admin_name}` : ''}
      </Text>
      {impersonation.reason ? (
        <Text style={styles.reason}>{impersonation.reason}</Text>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  banner: {
    backgroundColor: '#fef3c7',
    borderBottomColor: '#fcd34d',
    borderBottomWidth: 1,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  title: {
    color: '#78350f',
    fontSize: 14,
    fontWeight: '700',
  },
  body: {
    color: '#92400e',
    fontSize: 13,
    marginTop: 4,
  },
  reason: {
    color: '#a16207',
    fontSize: 12,
    marginTop: 4,
  },
});
