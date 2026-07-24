import { Link, Stack, type Href } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

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
  visible: boolean;
};

export default function SettingsHubScreen() {
  const { permissions } = useAuth();

  const links: SettingsLink[] = [
    {
      href: '/(app)/settings/organization',
      title: 'Organization',
      body: 'View and edit organization details.',
      visible: canViewOrganization(permissions),
    },
    {
      href: '/(app)/settings/billing',
      title: 'Billing',
      body: 'Subscription, plans, and payment portal.',
      visible: canViewOrganization(permissions),
    },
    {
      href: '/(app)/settings/team',
      title: 'Team',
      body: 'Manage team members and invitations.',
      visible: canManageUsers(permissions),
    },
    {
      href: '/(app)/settings/roles',
      title: 'Roles',
      body: 'Create and manage custom roles.',
      visible: canManageRoles(permissions),
    },
    {
      href: '/(app)/settings/privacy',
      title: 'Privacy & data',
      body: 'Export data and request account deletion.',
      visible: canViewOrganization(permissions),
    },
    {
      href: '/(app)/settings/sync',
      title: 'Sync status',
      body: 'Pending changes and manual sync.',
      visible: true,
    },
  ];

  const visibleLinks = links.filter((link) => link.visible);

  return (
    <>
      <Stack.Screen options={{ title: 'Settings' }} />
      <View style={styles.container}>
        <Text style={styles.title}>Settings</Text>
        <Text style={styles.description}>
          Manage your organization, team, billing, and data preferences.
        </Text>

        {visibleLinks.map((link) => (
          <Link key={link.href.toString()} href={link.href} asChild>
            <Pressable style={styles.card}>
              <Text style={styles.cardTitle}>{link.title}</Text>
              <Text style={styles.cardBody}>{link.body}</Text>
            </Pressable>
          </Link>
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
  card: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 12,
    padding: 16,
  },
  cardTitle: {
    color: '#0f172a',
    fontSize: 17,
    fontWeight: '700',
  },
  cardBody: {
    color: '#64748b',
    fontSize: 14,
    lineHeight: 20,
    marginTop: 6,
  },
});
