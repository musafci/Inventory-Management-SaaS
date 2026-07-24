import { type Href } from 'expo-router';

import { HubCard } from '@/components/HubCard';
import { HubScreenLayout } from '@/components/ui';
import { useAuth } from '@/src/auth/AuthContext';
import { canCreateCustomer } from '@/src/permissions';
import type { LegacyAccentTone } from '@/src/theme';
import type { AppIcon } from '@/src/theme/icons';

type SalesLink = {
  href: Href;
  title: string;
  body: string;
  testID: string;
  visible?: boolean;
  tone?: LegacyAccentTone;
  icon?: AppIcon;
};

export default function SalesScreen() {
  const { permissions } = useAuth();

  const links: SalesLink[] = [
    {
      href: '/(app)/customers',
      title: 'Customers',
      body: 'Browse, create, edit, and delete customers.',
      testID: 'hub-customers',
      tone: 'emerald',
      icon: { ios: 'person.2.fill', android: 'groups', web: 'groups' },
    },
    {
      href: '/(app)/sales-orders',
      title: 'Sales orders',
      body: 'Create orders, fulfill shipments, and collect payments.',
      testID: 'hub-sales-orders',
      tone: 'sky',
      icon: { ios: 'cart.fill', android: 'shopping_cart', web: 'shopping_cart' },
    },
    {
      href: '/(app)/payments',
      title: 'Payments',
      body: 'Review payment history and transaction details.',
      testID: 'hub-payments',
      tone: 'sky',
      icon: { ios: 'creditcard.fill', android: 'payments', web: 'payments' },
    },
    {
      href: '/(app)/imports/customers' as Href,
      title: 'Import customers (CSV)',
      body: 'Bulk upload customers from a CSV file.',
      testID: 'hub-import-customers',
      visible: canCreateCustomer(permissions),
      tone: 'violet',
      icon: { ios: 'square.and.arrow.down.fill', android: 'download', web: 'download' },
    },
  ];

  return (
    <HubScreenLayout
      description="Manage customers, sales orders, and payments from the modules below."
      eyebrow="Sales"
      title="Revenue & customers">
      {links.filter((link) => link.visible !== false).map((link) => (
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
  );
}
