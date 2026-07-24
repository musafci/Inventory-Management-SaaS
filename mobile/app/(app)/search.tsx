import { Stack, useRouter } from 'expo-router';
import { useMemo, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { SymbolView } from 'expo-symbols';

import {
  ListRow,
  ScreenContainer,
  SearchBar,
  SkeletonRow,
} from '@/components/ui';
import { useProducts, useProductsList } from '@/src/hooks/useProducts';
import { useCustomersList, useCustomers } from '@/src/hooks/usePartners';
import { useSuppliersList, useSuppliers } from '@/src/hooks/usePartners';
import { usePurchaseOrdersList, usePurchaseOrders } from '@/src/hooks/useOrders';
import { useSalesOrdersList, useSalesOrders } from '@/src/hooks/useOrders';
import { theme, palette } from '@/src/theme';
import { appIcon } from '@/src/theme/icons';
import type { Product, Customer, Supplier, PurchaseOrder, SalesOrder } from '@/src/api/types';

type SearchResult = {
  id: string;
  type: 'product' | 'customer' | 'supplier' | 'po' | 'so';
  title: string;
  subtitle: string;
  href: string;
};

const typeConfig = {
  product: { label: 'Product', color: palette.primary600, bg: palette.primary50, icon: { ios: 'box.fill' as const, android: 'inventory_2' as const, web: 'inventory_2' as const } },
  customer: { label: 'Customer', color: palette.emerald600, bg: palette.emerald50, icon: { ios: 'person.fill' as const, android: 'person' as const, web: 'person' as const } },
  supplier: { label: 'Supplier', color: palette.amber700, bg: palette.amber50, icon: { ios: 'truck.fill' as const, android: 'local_shipping' as const, web: 'local_shipping' as const } },
  po: { label: 'PO', color: palette.violet600, bg: palette.violet50, icon: { ios: 'cart.fill' as const, android: 'shopping_cart' as const, web: 'shopping_cart' as const } },
  so: { label: 'SO', color: palette.cyan600, bg: palette.cyan50, icon: { ios: 'dollarsign.circle.fill' as const, android: 'receipt' as const, web: 'receipt' as const } },
} as const;

function SearchSection({
  title,
  icon,
  items,
  onItemPress,
}: {
  title: string;
  icon: { ios: string; android: string; web: string };
  items: SearchResult[];
  onItemPress: (href: string) => void;
}) {
  if (items.length === 0) return null;

  return (
    <View style={styles.section}>
      <View style={styles.sectionHeader}>
        <SymbolView name={appIcon(icon)} size={16} tintColor={theme.colors.textSecondary} />
        <Text style={styles.sectionTitle}>{title}</Text>
        <Text style={styles.sectionCount}>{items.length}</Text>
      </View>
      {items.map((item) => {
        const config = typeConfig[item.type];
        return (
          <ListRow
            key={item.id}
            title={item.title}
            subtitle={item.subtitle}
            right={
              <View style={[styles.typeBadge, { backgroundColor: config.bg }]}>
                <SymbolView name={appIcon(config.icon)} size={12} tintColor={config.color} />
                <Text style={[styles.typeLabel, { color: config.color }]}>{config.label}</Text>
              </View>
            }
            onPress={() => onItemPress(item.href)}
          />
        );
      })}
    </View>
  );
}

export default function GlobalSearchScreen() {
  const router = useRouter();
  const [query, setQuery] = useState('');
  const search = query.trim();

  const products = useProductsList(search);
  const customers = useCustomersList(search);
  const suppliers = useSuppliersList(search);
  const purchaseOrders = usePurchaseOrdersList(search);
  const salesOrders = useSalesOrdersList(search);

  const productsQuery = useProducts(search);
  const customersQuery = useCustomers(search);
  const suppliersQuery = useSuppliers(search);
  const poQuery = usePurchaseOrders(search);
  const soQuery = useSalesOrders(search);

  const isLoading = productsQuery.isLoading || customersQuery.isLoading || suppliersQuery.isLoading || poQuery.isLoading || soQuery.isLoading;

  const productResults = useMemo<SearchResult[]>(() =>
    products.slice(0, 3).map((p: Product) => ({
      id: `product-${p.id}`,
      type: 'product' as const,
      title: p.name,
      subtitle: p.sku ? `SKU ${p.sku}` : p.barcode ?? 'No SKU',
      href: `/(app)/products/${p.id}`,
    })),
    [products],
  );

  const customerResults = useMemo<SearchResult[]>(() =>
    customers.slice(0, 3).map((c: Customer) => ({
      id: `customer-${c.id}`,
      type: 'customer' as const,
      title: c.name,
      subtitle: c.email ?? c.phone ?? 'No contact',
      href: `/(app)/customers/${c.id}`,
    })),
    [customers],
  );

  const supplierResults = useMemo<SearchResult[]>(() =>
    suppliers.slice(0, 3).map((s: Supplier) => ({
      id: `supplier-${s.id}`,
      type: 'supplier' as const,
      title: s.name,
      subtitle: s.email ?? s.phone ?? s.contact_person ?? 'No contact',
      href: `/(app)/suppliers/${s.id}`,
    })),
    [suppliers],
  );

  const poResults = useMemo<SearchResult[]>(() =>
    purchaseOrders.slice(0, 2).map((po: PurchaseOrder) => ({
      id: `po-${po.id}`,
      type: 'po' as const,
      title: po.po_number,
      subtitle: `${po.supplier?.name ?? 'Supplier'} · ${po.total_amount}`,
      href: `/(app)/purchase-orders/${po.id}`,
    })),
    [purchaseOrders],
  );

  const soResults = useMemo<SearchResult[]>(() =>
    salesOrders.slice(0, 2).map((so: SalesOrder) => ({
      id: `so-${so.id}`,
      type: 'so' as const,
      title: so.order_number,
      subtitle: `${so.customer?.name ?? 'Customer'} · ${so.total_amount}`,
      href: `/(app)/sales-orders/${so.id}`,
    })),
    [salesOrders],
  );

  const totalResults = productResults.length + customerResults.length + supplierResults.length + poResults.length + soResults.length;
  const hasResults = totalResults > 0;

  const navigate = (href: string) => {
    router.push(href as any);
  };

  return (
    <>
      <Stack.Screen
        options={{
          title: 'Search',
          headerTitleStyle: styles.headerTitle,
        }}
      />
      <ScreenContainer>
        <SearchBar
          accessibilityLabel="Global search"
          placeholder="Search products, customers, suppliers, orders..."
          value={query}
          onChangeText={setQuery}
          autoCapitalize="none"
          autoFocus
        />

        {!search ? (
          <View style={styles.empty}>
            <View style={styles.emptyIconWrap}>
              <SymbolView
                name={appIcon({ ios: 'magnifyingglass', android: 'search', web: 'search' })}
                size={28}
                tintColor={theme.colors.primary}
              />
            </View>
            <Text style={styles.emptyTitle}>Search everything</Text>
            <Text style={styles.emptySubtitle}>
              Find products, customers, suppliers,{'\n'}purchase orders and sales orders
            </Text>
          </View>
        ) : isLoading && !hasResults ? (
          <View style={styles.loadingContainer}>
            <SkeletonRow />
            <SkeletonRow />
            <SkeletonRow />
            <SkeletonRow />
          </View>
        ) : hasResults ? (
          <ScrollView style={styles.resultsScroll} keyboardShouldPersistTaps="handled">
            <View style={styles.resultsHeader}>
              <Text style={styles.resultsCount}>
                {totalResults} result{totalResults !== 1 ? 's' : ''} for "{search}"
              </Text>
            </View>

            <SearchSection
              title="Products"
              icon={typeConfig.product.icon}
              items={productResults}
              onItemPress={navigate}
            />

            <SearchSection
              title="Customers"
              icon={typeConfig.customer.icon}
              items={customerResults}
              onItemPress={navigate}
            />

            <SearchSection
              title="Suppliers"
              icon={typeConfig.supplier.icon}
              items={supplierResults}
              onItemPress={navigate}
            />

            <SearchSection
              title="Purchase Orders"
              icon={typeConfig.po.icon}
              items={poResults}
              onItemPress={navigate}
            />

            <SearchSection
              title="Sales Orders"
              icon={typeConfig.so.icon}
              items={soResults}
              onItemPress={navigate}
            />

            <View style={styles.bottomPadding} />
          </ScrollView>
        ) : (
          <View style={styles.empty}>
            <View style={[styles.emptyIconWrap, styles.emptyIconNoResults]}>
              <SymbolView
                name={appIcon({ ios: 'magnifyingglass', android: 'search', web: 'search' })}
                size={28}
                tintColor={theme.colors.textMuted}
              />
            </View>
            <Text style={styles.emptyTitle}>No results</Text>
            <Text style={styles.emptySubtitle}>
              Try a different search term
            </Text>
          </View>
        )}
      </ScreenContainer>
    </>
  );
}

const styles = StyleSheet.create({
  headerTitle: {
    fontWeight: '700',
  },
  empty: {
    alignItems: 'center',
    gap: theme.spacing.md,
    marginTop: 80,
    paddingHorizontal: theme.spacing.xl,
  },
  emptyIconWrap: {
    alignItems: 'center',
    backgroundColor: theme.colors.primarySoft,
    borderRadius: 32,
    height: 64,
    justifyContent: 'center',
    width: 64,
  },
  emptyIconNoResults: {
    backgroundColor: theme.colors.surfaceMuted,
  },
  emptyTitle: {
    ...theme.typography.heading,
    color: theme.colors.text,
  },
  emptySubtitle: {
    ...theme.typography.body,
    color: theme.colors.textSecondary,
    textAlign: 'center',
  },
  loadingContainer: {
    gap: theme.spacing.sm,
    marginTop: theme.spacing.md,
  },
  resultsScroll: {
    flex: 1,
  },
  resultsHeader: {
    paddingHorizontal: theme.spacing.lg,
    paddingVertical: theme.spacing.md,
  },
  resultsCount: {
    ...theme.typography.caption,
    color: theme.colors.textSecondary,
  },
  section: {
    marginBottom: theme.spacing.md,
  },
  sectionHeader: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: theme.spacing.sm,
    paddingHorizontal: theme.spacing.lg,
    paddingVertical: theme.spacing.sm,
  },
  sectionTitle: {
    ...theme.typography.label,
    color: theme.colors.textSecondary,
    flex: 1,
    letterSpacing: 0.4,
  },
  sectionCount: {
    ...theme.typography.caption,
    backgroundColor: theme.colors.surfaceMuted,
    borderRadius: theme.radius.pill,
    color: theme.colors.textMuted,
    fontWeight: '700',
    overflow: 'hidden',
    paddingHorizontal: 8,
    paddingVertical: 2,
  },
  typeBadge: {
    alignItems: 'center',
    borderRadius: theme.radius.pill,
    flexDirection: 'row',
    gap: 4,
    paddingHorizontal: 8,
    paddingVertical: 3,
  },
  typeLabel: {
    fontSize: 11,
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  bottomPadding: {
    height: theme.spacing.xxxl,
  },
});
