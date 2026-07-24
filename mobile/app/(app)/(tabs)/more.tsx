import { Link } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { SymbolView } from 'expo-symbols';

import { HubCard } from '@/components/HubCard';
import { Button, Card, HubScreenLayout } from '@/components/ui';
import { useAuth } from '@/src/auth/AuthContext';
import { canAccessSettings } from '@/src/permissions';
import { palette, shadow, theme } from '@/src/theme';

export default function MoreScreen() {
  const { logout, permissions, user } = useAuth();

  return (
    <HubScreenLayout description="Account shortcuts and workspace tools." eyebrow="Account" title={user?.name ?? 'Account'}>
      <LinearGradient
        colors={[palette.primary600, '#818cf8']}
        end={{ x: 1, y: 1 }}
        start={{ x: 0, y: 0 }}
        style={[styles.profileCard, shadow('md')]}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{(user?.name ?? 'U').slice(0, 1).toUpperCase()}</Text>
        </View>
        <View style={styles.profileBody}>
          <Text style={styles.profileName}>{user?.name ?? 'User'}</Text>
          <Text style={styles.profileEmail}>{user?.email}</Text>
        </View>
        <SymbolView
          name={{ ios: 'checkmark.seal.fill', android: 'verified', web: 'verified' }}
          size={22}
          tintColor="rgba(255,255,255,0.9)"
        />
      </LinearGradient>

      {canAccessSettings(permissions) ? (
        <HubCard
          body="Organization, billing, team, and roles."
          href="/(app)/settings"
          icon={{ ios: 'gearshape.fill', android: 'settings', web: 'settings' }}
          testID="hub-settings"
          title="Settings"
          tone="indigo"
        />
      ) : null}

      <HubCard
        body="View pending changes and sync now."
        href="/(app)/settings/sync"
        icon={{ ios: 'arrow.triangle.2.circlepath', android: 'sync', web: 'sync' }}
        testID="hub-sync-status"
        title="Sync status"
        tone="sky"
      />

      <HubCard
        body="View and revoke signed-in devices."
        href="/(app)/settings/sessions"
        icon={{ ios: 'iphone.and.arrow.forward', android: 'devices', web: 'devices' }}
        testID="hub-sessions"
        title="Active sessions"
        tone="violet"
      />

      <Card style={styles.logoutCard}>
        <Button
          accessibilityLabel="Sign out"
          label="Sign out"
          onPress={() => logout()}
          testID="sign-out-button"
          variant="danger"
        />
      </Card>
    </HubScreenLayout>
  );
}

const styles = StyleSheet.create({
  profileCard: {
    alignItems: 'center',
    borderRadius: theme.radius.xl,
    flexDirection: 'row',
    gap: theme.spacing.md,
    marginBottom: theme.spacing.lg,
    padding: theme.spacing.lg,
  },
  avatar: {
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.22)',
    borderRadius: theme.radius.pill,
    height: 52,
    justifyContent: 'center',
    width: 52,
  },
  avatarText: {
    color: theme.colors.primaryText,
    fontSize: 22,
    fontWeight: '800',
  },
  profileBody: {
    flex: 1,
  },
  profileName: {
    color: theme.colors.primaryText,
    fontSize: 18,
    fontWeight: '800',
  },
  profileEmail: {
    color: 'rgba(255,255,255,0.88)',
    fontSize: 14,
    marginTop: 4,
  },
  logoutCard: {
    marginTop: theme.spacing.sm,
  },
});
