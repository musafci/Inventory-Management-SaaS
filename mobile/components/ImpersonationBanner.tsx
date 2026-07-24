import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useAuth } from '@/src/auth/AuthContext';

export function ImpersonationBanner() {
  const { impersonation, organizations, organizationId, exitImpersonation } = useAuth();

  if (!impersonation?.active) {
    return null;
  }

  const organizationName = organizations.find((org) => org.id === organizationId)?.name ?? 'tenant';

  return (
    <View style={styles.banner}>
      <View style={styles.bodyWrap}>
        <Text style={styles.title}>Support impersonation active</Text>
        <Text style={styles.body}>
          Viewing {organizationName}
          {impersonation.platform_admin_name ? ` · ${impersonation.platform_admin_name}` : ''}
        </Text>
        {impersonation.reason ? (
          <Text style={styles.reason}>{impersonation.reason}</Text>
        ) : null}
      </View>
      <Pressable
        accessibilityLabel="Exit impersonation"
        accessibilityRole="button"
        onPress={() => {
          void exitImpersonation();
        }}
        style={styles.exitButton}>
        <Text style={styles.exitText}>Exit</Text>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  banner: {
    alignItems: 'center',
    backgroundColor: '#fef3c7',
    borderBottomColor: '#fcd34d',
    borderBottomWidth: 1,
    flexDirection: 'row',
    gap: 12,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  bodyWrap: {
    flex: 1,
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
  exitButton: {
    backgroundColor: '#fff',
    borderColor: '#f59e0b',
    borderRadius: 8,
    borderWidth: 1,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  exitText: {
    color: '#92400e',
    fontSize: 13,
    fontWeight: '700',
  },
});
