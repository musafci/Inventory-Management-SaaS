import { type Href } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import { HubCard } from '@/components/HubCard';
import { useAuth } from '@/src/auth/AuthContext';
import {
  canExportReports,
  canViewInventoryReports,
  canViewPurchaseReports,
  canViewSalesReports,
} from '@/src/permissions';

type ReportLink = {
  href: Href;
  title: string;
  body: string;
  testID: string;
  visible: boolean;
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
    },
    {
      href: '/(app)/reports/low-stock',
      title: 'Low stock',
      body: 'Products at or below their reorder point.',
      testID: 'hub-report-low-stock',
      visible: canViewInventoryReports(permissions),
    },
    {
      href: '/(app)/reports/sales-summary',
      title: 'Sales summary',
      body: 'Order counts and totals grouped by status.',
      testID: 'hub-report-sales-summary',
      visible: canViewSalesReports(permissions),
    },
    {
      href: '/(app)/reports/purchase-summary',
      title: 'Purchase summary',
      body: 'Purchase order totals and status breakdown.',
      testID: 'hub-report-purchase-summary',
      visible: canViewPurchaseReports(permissions),
    },
    {
      href: '/(app)/reports/exports',
      title: 'Report exports',
      body: 'Queue CSV exports and download when ready.',
      testID: 'hub-report-exports',
      visible: canExportReports(permissions),
    },
  ];

  const visibleLinks = links.filter((link) => link.visible);

  return (
    <View style={styles.container}>
      <Text accessibilityRole="header" style={styles.title}>Reports</Text>
      <Text style={styles.description}>
        View inventory, sales, and purchase reports for your organization.
      </Text>

      {visibleLinks.length === 0 ? (
        <View style={styles.emptyCard}>
          <Text style={styles.emptyBody}>You do not have permission to view any reports.</Text>
        </View>
      ) : (
        visibleLinks.map((link) => (
          <HubCard
            key={link.testID}
            href={link.href}
            title={link.title}
            body={link.body}
            testID={link.testID}
          />
        ))
      )}
    </View>
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
  emptyCard: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 12,
    borderWidth: 1,
    padding: 16,
  },
  emptyBody: {
    color: '#64748b',
    fontSize: 14,
    lineHeight: 20,
  },
});
