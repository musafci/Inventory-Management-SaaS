import { StyleSheet, Text, View } from 'react-native';
import { SymbolView } from 'expo-symbols';

import { AnimatedPressable } from '@/components/ui/AnimatedPressable';
import { useAuth } from '@/src/auth/AuthContext';
import { palette, theme } from '@/src/theme';

export function ImpersonationBanner() {
  const { impersonation, organizations, organizationId, exitImpersonation } = useAuth();

  if (!impersonation?.active) {
    return null;
  }

  const organizationName = organizations.find((org) => org.id === organizationId)?.name ?? 'tenant';

  return (
    <View style={styles.banner}>
      <View style={styles.iconWrap}>
        <SymbolView
          name={{ ios: 'eye.fill', android: 'visibility', web: 'visibility' }}
          size={16}
          tintColor={palette.amber700}
        />
      </View>
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
      <AnimatedPressable
        accessibilityLabel="Exit impersonation"
        accessibilityRole="button"
        onPress={() => {
          void exitImpersonation();
        }}
        style={styles.exitButton}>
        <Text style={styles.exitText}>Exit</Text>
      </AnimatedPressable>
    </View>
  );
}

const styles = StyleSheet.create({
  banner: {
    alignItems: 'center',
    backgroundColor: palette.amber50,
    borderBottomColor: '#fde68a',
    borderBottomWidth: 1,
    flexDirection: 'row',
    gap: theme.spacing.md,
    paddingHorizontal: theme.spacing.lg,
    paddingVertical: theme.spacing.md,
  },
  iconWrap: {
    alignItems: 'center',
    backgroundColor: theme.colors.surface,
    borderRadius: theme.radius.sm,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  bodyWrap: {
    flex: 1,
  },
  title: {
    color: palette.amber700,
    fontSize: 14,
    fontWeight: '800',
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
    backgroundColor: theme.colors.surface,
    borderColor: palette.amber500,
    borderRadius: theme.radius.pill,
    borderWidth: 1,
    paddingHorizontal: 14,
    paddingVertical: 8,
  },
  exitText: {
    color: palette.amber700,
    fontSize: 13,
    fontWeight: '800',
  },
});
