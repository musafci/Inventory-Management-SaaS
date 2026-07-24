import { Link, type Href } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

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
  visible: boolean;
};

export default function ReportsScreen() {
  const { permissions } = useAuth();

  const links: ReportLink[] = [
    {
      href: '/(app)/reports/stock-valuation',
      title: 'Stock valuation',
      body: 'Total inventory value and breakdown by warehouse.',
      visible: canViewInventoryReports(permissions),
    },
    {
      href: '/(app)/reports/low-stock',
      title: 'Low stock',
      body: 'Products at or below their reorder point.',
      visible: canViewInventoryReports(permissions),
    },
    {
      href: '/(app)/reports/sales-summary',
      title: 'Sales summary',
      body: 'Order counts and totals grouped by status.',
      visible: canViewSalesReports(permissions),
    },
    {
      href: '/(app)/reports/purchase-summary',
      title: 'Purchase summary',
      body: 'Purchase order totals and status breakdown.',
      visible: canViewPurchaseReports(permissions),
    },
    {
      href: '/(app)/reports/exports',
      title: 'Report exports',
      body: 'Queue CSV exports and download when ready.',
      visible: canExportReports(permissions),
    },
  ];

  const visibleLinks = links.filter((link) => link.visible);

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Reports</Text>
      <Text style={styles.description}>
        View inventory, sales, and purchase reports for your organization.
      </Text>

      {visibleLinks.length === 0 ? (
        <View style={styles.card}>
          <Text style={styles.cardBody}>You do not have permission to view any reports.</Text>
        </View>
      ) : (
        visibleLinks.map((link) => (
          <Link key={link.href.toString()} href={link.href} asChild>
            <Pressable style={styles.card}>
              <Text style={styles.cardTitle}>{link.title}</Text>
              <Text style={styles.cardBody}>{link.body}</Text>
            </Pressable>
          </Link>
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
