import { type Href } from 'expo-router';

import { HubCard } from '@/components/HubCard';
import { HubScreenLayout } from '@/components/ui';
import { useAuth } from '@/src/auth/AuthContext';
import { canCreateInventory } from '@/src/permissions';
import type { AccentTone } from '@/src/theme';
import type { AppIcon } from '@/src/theme/icons';

type InventoryLink = {
  href: Href;
  title: string;
  body: string;
  testID: string;
  visible?: boolean;
  tone?: AccentTone;
  icon?: AppIcon;
};

export default function InventoryScreen() {
  const { permissions } = useAuth();

  const links: InventoryLink[] = [
    {
      href: '/(app)/products',
      title: 'Products',
      body: 'Browse, search, create, and edit products.',
      testID: 'hub-products',
      tone: 'indigo',
      icon: { ios: 'cube.box.fill', android: 'inventory', web: 'inventory' },
    },
    {
      href: '/(app)/stocks',
      title: 'Stock levels',
      body: 'View on-hand and available quantities by warehouse.',
      testID: 'hub-stocks',
      tone: 'emerald',
      icon: { ios: 'chart.bar.doc.horizontal.fill', android: 'analytics', web: 'analytics' },
    },
    {
      href: '/(app)/stock-movements',
      title: 'Stock movements',
      body: 'Review ledger entries and record adjustments.',
      testID: 'hub-stock-movements',
      tone: 'violet',
      icon: { ios: 'arrow.left.arrow.right', android: 'sync_alt', web: 'sync_alt' },
    },
    {
      href: '/(app)/categories',
      title: 'Categories',
      body: 'Organize products into categories.',
      testID: 'hub-categories',
      tone: 'sky',
      icon: { ios: 'folder.fill', android: 'folder', web: 'folder' },
    },
    {
      href: '/(app)/units',
      title: 'Units',
      body: 'Manage measurement units for products.',
      testID: 'hub-units',
      tone: 'amber',
      icon: { ios: 'ruler.fill', android: 'straighten', web: 'straighten' },
    },
    {
      href: '/(app)/warehouses',
      title: 'Warehouses',
      body: 'Manage storage locations for stock and orders.',
      testID: 'hub-warehouses',
      tone: 'rose',
      icon: { ios: 'building.2.fill', android: 'warehouse', web: 'warehouse' },
    },
    {
      href: '/(app)/imports/products' as Href,
      title: 'Import products (CSV)',
      body: 'Bulk upload products from a CSV file.',
      testID: 'hub-import-products',
      visible: canCreateInventory(permissions),
      tone: 'indigo',
      icon: { ios: 'square.and.arrow.down.fill', android: 'download', web: 'download' },
    },
  ];

  return (
    <HubScreenLayout
      description="Manage catalog data and stock from the modules below."
      eyebrow="Inventory"
      title="Your catalog & stock">
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
