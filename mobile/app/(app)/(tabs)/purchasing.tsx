import { type Href } from 'expo-router';

import { HubCard } from '@/components/HubCard';
import { HubScreenLayout } from '@/components/ui';
import { useAuth } from '@/src/auth/AuthContext';
import { canCreateSupplier } from '@/src/permissions';
import type { LegacyAccentTone } from '@/src/theme';
import type { AppIcon } from '@/src/theme/icons';

type PurchasingLink = {
  href: Href;
  title: string;
  body: string;
  testID: string;
  visible?: boolean;
  tone?: LegacyAccentTone;
  icon?: AppIcon;
};

export default function PurchasingScreen() {
  const { permissions } = useAuth();

  const links: PurchasingLink[] = [
    {
      href: '/(app)/suppliers',
      title: 'Suppliers',
      body: 'Browse, create, edit, and delete suppliers.',
      testID: 'hub-suppliers',
      tone: 'amber',
      icon: { ios: 'person.crop.rectangle.fill', android: 'business', web: 'business' },
    },
    {
      href: '/(app)/purchase-orders',
      title: 'Purchase orders',
      body: 'Create orders, receive stock, and record payments.',
      testID: 'hub-purchase-orders',
      tone: 'sky',
      icon: { ios: 'shippingbox.fill', android: 'inventory_2', web: 'inventory_2' },
    },
    {
      href: '/(app)/imports/suppliers' as Href,
      title: 'Import suppliers (CSV)',
      body: 'Bulk upload suppliers from a CSV file.',
      testID: 'hub-import-suppliers',
      visible: canCreateSupplier(permissions),
      tone: 'violet',
      icon: { ios: 'square.and.arrow.down.fill', android: 'download', web: 'download' },
    },
  ];

  return (
    <HubScreenLayout
      description="Manage suppliers and purchase orders from the modules below."
      eyebrow="Purchasing"
      title="Procurement hub">
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
