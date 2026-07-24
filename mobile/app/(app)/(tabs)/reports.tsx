import { type Href } from 'expo-router';

import { HubCard } from '@/components/HubCard';
import { Card, EmptyState, HubScreenLayout } from '@/components/ui';
import { useAuth } from '@/src/auth/AuthContext';
import {
  canExportReports,
  canViewInventoryReports,
  canViewPurchaseReports,
  canViewSalesReports,
} from '@/src/permissions';
import type { AccentTone } from '@/src/theme';
import type { AppIcon } from '@/src/theme/icons';

type ReportLink = {
  href: Href;
  title: string;
  body: string;
  testID: string;
  visible: boolean;
  tone?: AccentTone;
  icon?: AppIcon;
};

export default function ReportsScreen() {
  const { permissions } = useAuth();

  const links: ReportLink[] = [
    {
      href: '/(app)/reports/stock-valuation',
      title: 'Stock valuation',
      body: 'Total inventory value and breakdown by warehouse.',
      testID: 'hub-report-stock-valuation',
      visible: canViewInventoryReports(permissions),
      tone: 'indigo',
      icon: { ios: 'dollarsign.circle.fill', android: 'paid', web: 'paid' },
    },
    {
      href: '/(app)/reports/low-stock',
      title: 'Low stock',
      body: 'Products at or below their reorder point.',
      testID: 'hub-report-low-stock',
      visible: canViewInventoryReports(permissions),
      tone: 'amber',
      icon: { ios: 'exclamationmark.triangle.fill', android: 'warning', web: 'warning' },
    },
    {
      href: '/(app)/reports/sales-summary',
      title: 'Sales summary',
      body: 'Order counts and totals grouped by status.',
      testID: 'hub-report-sales-summary',
      visible: canViewSalesReports(permissions),
      tone: 'emerald',
      icon: { ios: 'chart.line.uptrend.xyaxis', android: 'trending_up', web: 'trending_up' },
    },
    {
      href: '/(app)/reports/purchase-summary',
      title: 'Purchase summary',
      body: 'Purchase order totals and status breakdown.',
      testID: 'hub-report-purchase-summary',
      visible: canViewPurchaseReports(permissions),
      tone: 'sky',
      icon: { ios: 'chart.bar.fill', android: 'bar_chart', web: 'bar_chart' },
    },
    {
      href: '/(app)/reports/exports',
      title: 'Report exports',
      body: 'Queue CSV exports and download when ready.',
      testID: 'hub-report-exports',
      visible: canExportReports(permissions),
      tone: 'violet',
      icon: { ios: 'square.and.arrow.up.fill', android: 'upload', web: 'upload' },
    },
  ];

  const visibleLinks = links.filter((link) => link.visible);

  return (
    <HubScreenLayout
      description="View inventory, sales, and purchase reports for your organization."
      eyebrow="Reports"
      title="Insights & exports">
      {visibleLinks.length === 0 ? (
        <Card muted>
          <EmptyState
            body="Ask an admin to grant report permissions."
            title="You do not have permission to view any reports."
          />
        </Card>
      ) : (
        visibleLinks.map((link) => (
          <HubCard
            key={link.testID}
            body={link.body}
            href={link.href}
            icon={link.icon}
            testID={link.testID}
            title={link.title}
            tone={link.tone}
          />
        ))
      )}
    </HubScreenLayout>
  );
}
