import { Stack, type Href } from 'expo-router';

import { HubCard } from '@/components/HubCard';
import { HubScreenLayout } from '@/components/ui';
import { useAuth } from '@/src/auth/AuthContext';
import {
  canManageRoles,
  canManageUsers,
  canViewOrganization,
} from '@/src/permissions';
import type { AccentTone } from '@/src/theme';
import type { AppIcon } from '@/src/theme/icons';

type SettingsLink = {
  href: Href;
  title: string;
  body: string;
  testID: string;
  visible: boolean;
  tone?: AccentTone;
  icon?: AppIcon;
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
      tone: 'indigo',
      icon: { ios: 'building.2.fill', android: 'business', web: 'business' },
    },
    {
      href: '/(app)/settings/billing',
      title: 'Billing',
      body: 'Subscription, plans, and payment portal.',
      testID: 'hub-settings-billing',
      visible: canViewOrganization(permissions),
      tone: 'emerald',
      icon: { ios: 'creditcard.fill', android: 'payments', web: 'payments' },
    },
    {
      href: '/(app)/settings/team',
      title: 'Team',
      body: 'Manage team members and invitations.',
      testID: 'hub-settings-team',
      visible: canManageUsers(permissions),
      tone: 'sky',
      icon: { ios: 'person.3.fill', android: 'groups', web: 'groups' },
    },
    {
      href: '/(app)/settings/roles',
      title: 'Roles',
      body: 'Create and manage custom roles.',
      testID: 'hub-settings-roles',
      visible: canManageRoles(permissions),
      tone: 'violet',
      icon: { ios: 'key.fill', android: 'vpn_key', web: 'vpn_key' },
    },
    {
      href: '/(app)/settings/privacy',
      title: 'Privacy & data',
      body: 'Export data and request account deletion.',
      testID: 'hub-settings-privacy',
      visible: canViewOrganization(permissions),
      tone: 'rose',
      icon: { ios: 'hand.raised.fill', android: 'privacy_tip', web: 'privacy_tip' },
    },
    {
      href: '/(app)/settings/notifications' as Href,
      title: 'Notifications',
      body: 'Push notification preferences.',
      testID: 'hub-settings-notifications',
      visible: canViewOrganization(permissions),
      tone: 'amber',
      icon: { ios: 'bell.fill', android: 'notifications', web: 'notifications' },
    },
    {
      href: '/(app)/settings/sync',
      title: 'Sync status',
      body: 'Pending changes and manual sync.',
      testID: 'hub-settings-sync',
      visible: true,
      tone: 'indigo',
      icon: { ios: 'arrow.triangle.2.circlepath', android: 'sync', web: 'sync' },
    },
    {
      href: '/(app)/settings/sessions' as Href,
      title: 'Active sessions',
      body: 'View and revoke signed-in devices.',
      testID: 'hub-settings-sessions',
      visible: true,
      tone: 'sky',
      icon: { ios: 'iphone.and.arrow.forward', android: 'devices', web: 'devices' },
    },
  ];

  const visibleLinks = links.filter((link) => link.visible);

  return (
    <>
      <Stack.Screen options={{ title: 'Settings' }} />
      <HubScreenLayout
        description="Manage your organization, team, billing, and data preferences."
        eyebrow="Settings"
        title="Workspace controls">
        {visibleLinks.map((link) => (
          <HubCard
            key={link.testID}
            body={link.body}
            href={link.href}
            icon={link.icon}
            testID={link.testID}
            title={link.title}
            tone={link.tone}
          />
        ))}
      </HubScreenLayout>
    </>
  );
}
