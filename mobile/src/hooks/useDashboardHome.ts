import { useQuery } from '@tanstack/react-query';

import * as ordersApi from '@/src/api/orders';
import * as reportsApi from '@/src/api/reports';
import type { LowStockItem, PurchaseOrder, SalesOrder } from '@/src/api/types';
import { useAuth } from '@/src/auth/AuthContext';

export type RecentOrder = {
  id: number;
  kind: 'purchase' | 'sales';
  number: string;
  status: string;
  total_amount: string;
  created_at: string | null;
};

function mapRecentOrders(
  purchaseOrders: PurchaseOrder[],
  salesOrders: SalesOrder[],
): RecentOrder[] {
  const merged: RecentOrder[] = [
    ...purchaseOrders.map((order) => ({
      id: order.id,
      kind: 'purchase' as const,
      number: order.po_number,
      status: order.status,
      total_amount: order.total_amount,
      created_at: order.created_at,
    })),
    ...salesOrders.map((order) => ({
      id: order.id,
      kind: 'sales' as const,
      number: order.order_number,
      status: order.status,
      total_amount: order.total_amount,
      created_at: order.created_at,
    })),
  ];

  return merged
    .sort((a, b) => (b.created_at ?? '').localeCompare(a.created_at ?? ''))
    .slice(0, 10);
}

export function useDashboardHome() {
  const { organizationId } = useAuth();

  const dashboardQuery = useQuery({
    queryKey: ['dashboard', organizationId],
    enabled: organizationId !== null,
    queryFn: () => reportsApi.fetchDashboard(organizationId),
  });

  const recentOrdersQuery = useQuery({
    queryKey: ['dashboard-recent-orders', organizationId],
    enabled: organizationId !== null,
    queryFn: async () => {
      const [purchaseOrders, salesOrders] = await Promise.all([
        ordersApi.fetchPurchaseOrders({
          organizationId,
          page: 1,
          perPage: 5,
          sort: '-created_at',
        }),
        ordersApi.fetchSalesOrders({
          organizationId,
          page: 1,
          perPage: 5,
          sort: '-created_at',
        }),
      ]);

      return mapRecentOrders(purchaseOrders.orders, salesOrders.orders);
    },
  });

  const lowStockQuery = useQuery({
    queryKey: ['dashboard-low-stock', organizationId],
    enabled: organizationId !== null,
    queryFn: () => reportsApi.fetchLowStock(organizationId),
  });

  const refetch = async () => {
    await Promise.all([
      dashboardQuery.refetch(),
      recentOrdersQuery.refetch(),
      lowStockQuery.refetch(),
    ]);
  };

  return {
    dashboardQuery,
    recentOrders: recentOrdersQuery.data ?? [],
    lowStockItems: (lowStockQuery.data ?? []).slice(0, 5) as LowStockItem[],
    isRefetching:
      dashboardQuery.isRefetching ||
      recentOrdersQuery.isRefetching ||
      lowStockQuery.isRefetching,
    refetch,
  };
}
