import { Stack, type Href } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import { HubCard } from '@/components/HubCard';
import { useAuth } from '@/src/auth/AuthContext';
import {
  canManageRoles,
  canManageUsers,
  canViewOrganization,
} from '@/src/permissions';

type SettingsLink = {
  href: Href;
  title: string;
  body: string;
  testID: string;
  visible: boolean;
};

export default function SettingsHubScreen() {
  const { permissions } = useAuth();

  const links: SettingsLink[] = [
    {
      href: '/(app)/settings/organization',
      title: 'Organization',
      body: 'View and edit organization details.',
      testID: 'hub-settings-organization',
      visible: canViewOrganization(permissions),
    },
    {
      href: '/(app)/settings/billing',
      title: 'Billing',
      body: 'Subscription, plans, and payment portal.',
      testID: 'hub-settings-billing',
      visible: canViewOrganization(permissions),
    },
    {
      href: '/(app)/settings/team',
      title: 'Team',
      body: 'Manage team members and invitations.',
      testID: 'hub-settings-team',
      visible: canManageUsers(permissions),
    },
    {
      href: '/(app)/settings/roles',
      title: 'Roles',
      body: 'Create and manage custom roles.',
      testID: 'hub-settings-roles',
      visible: canManageRoles(permissions),
    },
    {
      href: '/(app)/settings/privacy',
      title: 'Privacy & data',
      body: 'Export data and request account deletion.',
      testID: 'hub-settings-privacy',
      visible: canViewOrganization(permissions),
    },
    {
      href: '/(app)/settings/notifications' as Href,
      title: 'Notifications',
      body: 'Push notification preferences.',
      testID: 'hub-settings-notifications',
      visible: canViewOrganization(permissions),
    },
    {
      href: '/(app)/settings/sync',
      title: 'Sync status',
      body: 'Pending changes and manual sync.',
      testID: 'hub-settings-sync',
      visible: true,
    },
    {
      href: '/(app)/settings/sessions' as Href,
      title: 'Active sessions',
      body: 'View and revoke signed-in devices.',
      testID: 'hub-settings-sessions',
      visible: true,
    },
  ];

  const visibleLinks = links.filter((link) => link.visible);

  return (
    <>
      <Stack.Screen options={{ title: 'Settings' }} />
      <View style={styles.container}>
        <Text accessibilityRole="header" style={styles.title}>Settings</Text>
        <Text style={styles.description}>
          Manage your organization, team, billing, and data preferences.
        </Text>

        {visibleLinks.map((link) => (
          <HubCard
            key={link.testID}
            href={link.href}
            title={link.title}
            body={link.body}
            testID={link.testID}
          />
        ))}
      </View>
    </>
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
  description: {
    color: '#64748b',
    fontSize: 15,
    lineHeight: 22,
    marginBottom: 20,
    marginTop: 10,
  },
});
