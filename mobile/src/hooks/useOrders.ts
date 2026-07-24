import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';

import * as ordersApi from '@/src/api/orders';
import type {
  FulfillSalesOrderPayload,
  PaymentPayload,
  PurchaseOrderPayload,
  ReceivePurchaseOrderPayload,
  RefundPayload,
  SalesOrderPayload,
} from '@/src/api/types';
import { useAuth } from '@/src/auth/AuthContext';
import {
  deleteCachedOrder,
  getCachedPurchaseOrder,
  getCachedSalesOrder,
  listCachedPurchaseOrders,
  listCachedSalesOrders,
  upsertPurchaseOrders,
  upsertSalesOrders,
} from '@/src/db/ordersCache';
import { upsertPayments } from '@/src/db/paymentsCache';
import { enqueueMutation } from '@/src/db/outbox';
import { useNetwork } from '@/src/network/NetworkContext';
import { enqueueOrExecute } from '@/src/sync/enqueueOrExecute';
import { generateIdempotencyKey } from '@/src/utils/idempotency';

export function usePurchaseOrders(search: string) {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useInfiniteQuery({
    queryKey: ['purchase-orders', organizationId, search, isConnected],
    enabled: organizationId !== null,
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      if (organizationId === null) {
        return { orders: [], pagination: { current_page: 1, per_page: 0, total: 0, last_page: 1 } };
      }

      if (!isConnected) {
        const orders = await listCachedPurchaseOrders(organizationId, search);

        return {
          orders,
          pagination: {
            current_page: 1,
            per_page: orders.length,
            total: orders.length,
            last_page: 1,
          },
        };
      }

      const response = await ordersApi.fetchPurchaseOrders({
        page: pageParam,
        perPage: 50,
        search,
        organizationId,
      });

      await upsertPurchaseOrders(organizationId, response.orders);

      return response;
    },
    getNextPageParam: (lastPage) => {
      if (lastPage.pagination.current_page >= lastPage.pagination.last_page) {
        return undefined;
      }

      return lastPage.pagination.current_page + 1;
    },
  });
}

export function usePurchaseOrdersList(search: string) {
  const query = usePurchaseOrders(search);

  return useMemo(
    () => query.data?.pages.flatMap((page) => page.orders) ?? [],
    [query.data?.pages],
  );
}

export function useSalesOrders(search: string) {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useInfiniteQuery({
    queryKey: ['sales-orders', organizationId, search, isConnected],
    enabled: organizationId !== null,
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      if (organizationId === null) {
        return { orders: [], pagination: { current_page: 1, per_page: 0, total: 0, last_page: 1 } };
      }

      if (!isConnected) {
        const orders = await listCachedSalesOrders(organizationId, search);

        return {
          orders,
          pagination: {
            current_page: 1,
            per_page: orders.length,
            total: orders.length,
            last_page: 1,
          },
        };
      }

      const response = await ordersApi.fetchSalesOrders({
        page: pageParam,
        perPage: 50,
        search,
        organizationId,
      });

      await upsertSalesOrders(organizationId, response.orders);

      return response;
    },
    getNextPageParam: (lastPage) => {
      if (lastPage.pagination.current_page >= lastPage.pagination.last_page) {
        return undefined;
      }

      return lastPage.pagination.current_page + 1;
    },
  });
}

export function useSalesOrdersList(search: string) {
  const query = useSalesOrders(search);

  return useMemo(
    () => query.data?.pages.flatMap((page) => page.orders) ?? [],
    [query.data?.pages],
  );
}

export function usePurchaseOrder(orderId: number | null) {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useQuery({
    queryKey: ['purchase-order', organizationId, orderId, isConnected],
    enabled: organizationId !== null && orderId !== null,
    queryFn: async () => {
      if (organizationId === null || orderId === null) {
        return null;
      }

      if (!isConnected) {
        return getCachedPurchaseOrder(organizationId, orderId);
      }

      const order = await ordersApi.fetchPurchaseOrder(orderId, organizationId);
      await upsertPurchaseOrders(organizationId, [order]);

      return order;
    },
  });
}

export function useSalesOrder(orderId: number | null) {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useQuery({
    queryKey: ['sales-order', organizationId, orderId, isConnected],
    enabled: organizationId !== null && orderId !== null,
    queryFn: async () => {
      if (organizationId === null || orderId === null) {
        return null;
      }

      if (!isConnected) {
        return getCachedSalesOrder(organizationId, orderId);
      }

      const order = await ordersApi.fetchSalesOrder(orderId, organizationId);
      await upsertSalesOrders(organizationId, [order]);

      return order;
    },
  });
}

export function useCreatePurchaseOrder() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useMutation({
    mutationFn: async (payload: PurchaseOrderPayload) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      const idempotencyKey = generateIdempotencyKey();

      if (!isConnected) {
        await enqueueMutation({
          organizationId,
          method: 'POST',
          path: '/v1/purchase-orders',
          body: payload,
          idempotencyKey,
        });

        return null;
      }

      return ordersApi.createPurchaseOrder(payload, organizationId, idempotencyKey);
    },
    onSuccess: async (order) => {
      if (organizationId !== null && order) {
        await upsertPurchaseOrders(organizationId, [order]);
      }

      await queryClient.invalidateQueries({ queryKey: ['purchase-orders'] });
    },
  });
}

export function useCreateSalesOrder() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useMutation({
    mutationFn: async (payload: SalesOrderPayload) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      const idempotencyKey = generateIdempotencyKey();

      if (!isConnected) {
        await enqueueMutation({
          organizationId,
          method: 'POST',
          path: '/v1/sales-orders',
          body: payload,
          idempotencyKey,
        });

        return null;
      }

      return ordersApi.createSalesOrder(payload, organizationId, idempotencyKey);
    },
    onSuccess: async (order) => {
      if (organizationId !== null && order) {
        await upsertSalesOrders(organizationId, [order]);
      }

      await queryClient.invalidateQueries({ queryKey: ['sales-orders'] });
    },
  });
}

export function useUpdatePurchaseOrder(orderId: number) {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (payload: Partial<PurchaseOrderPayload>) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return ordersApi.updatePurchaseOrder(orderId, payload, organizationId);
    },
    onSuccess: async (order) => {
      if (organizationId !== null) {
        await upsertPurchaseOrders(organizationId, [order]);
      }

      await queryClient.invalidateQueries({ queryKey: ['purchase-orders'] });
      await queryClient.invalidateQueries({ queryKey: ['purchase-order', organizationId, orderId] });
    },
  });
}

export function useUpdateSalesOrder(orderId: number) {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (payload: Partial<SalesOrderPayload>) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return ordersApi.updateSalesOrder(orderId, payload, organizationId);
    },
    onSuccess: async (order) => {
      if (organizationId !== null) {
        await upsertSalesOrders(organizationId, [order]);
      }

      await queryClient.invalidateQueries({ queryKey: ['sales-orders'] });
      await queryClient.invalidateQueries({ queryKey: ['sales-order', organizationId, orderId] });
    },
  });
}

export function useDeletePurchaseOrder() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (orderId: number) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return ordersApi.deletePurchaseOrder(orderId, organizationId);
    },
    onSuccess: async (_result, orderId) => {
      if (organizationId !== null) {
        await deleteCachedOrder(organizationId, orderId, 'purchase_order');
      }

      await queryClient.invalidateQueries({ queryKey: ['purchase-orders'] });
    },
  });
}

export function useDeleteSalesOrder() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (orderId: number) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return ordersApi.deleteSalesOrder(orderId, organizationId);
    },
    onSuccess: async (_result, orderId) => {
      if (organizationId !== null) {
        await deleteCachedOrder(organizationId, orderId, 'sales_order');
      }

      await queryClient.invalidateQueries({ queryKey: ['sales-orders'] });
    },
  });
}

export function useSendPurchaseOrder() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useMutation({
    mutationFn: async (orderId: number) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return enqueueOrExecute({
        organizationId,
        isConnected,
        method: 'POST',
        path: `/v1/purchase-orders/${orderId}/send`,
        onlineFn: () => ordersApi.sendPurchaseOrder(orderId, organizationId),
      });
    },
    onSuccess: async (order) => {
      if (organizationId !== null && order) {
        await upsertPurchaseOrders(organizationId, [order]);
      }

      await queryClient.invalidateQueries({ queryKey: ['purchase-orders'] });
      await queryClient.invalidateQueries({ queryKey: ['purchase-order'] });
    },
  });
}

export function useCancelPurchaseOrder() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useMutation({
    mutationFn: async (orderId: number) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return enqueueOrExecute({
        organizationId,
        isConnected,
        method: 'POST',
        path: `/v1/purchase-orders/${orderId}/cancel`,
        onlineFn: () => ordersApi.cancelPurchaseOrder(orderId, organizationId),
      });
    },
    onSuccess: async (order) => {
      if (organizationId !== null && order) {
        await upsertPurchaseOrders(organizationId, [order]);
      }

      await queryClient.invalidateQueries({ queryKey: ['purchase-orders'] });
      await queryClient.invalidateQueries({ queryKey: ['purchase-order'] });
    },
  });
}

export function useReceivePurchaseOrder() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useMutation({
    mutationFn: async ({ orderId, payload }: { orderId: number; payload: ReceivePurchaseOrderPayload }) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return enqueueOrExecute({
        organizationId,
        isConnected,
        method: 'POST',
        path: `/v1/purchase-orders/${orderId}/receive`,
        body: payload,
        onlineFn: () => ordersApi.receivePurchaseOrder(orderId, payload, organizationId),
      });
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['purchase-orders'] });
      await queryClient.invalidateQueries({ queryKey: ['purchase-order'] });
      await queryClient.invalidateQueries({ queryKey: ['stocks'] });
      await queryClient.invalidateQueries({ queryKey: ['stock-movements'] });
    },
  });
}

export function usePayPurchaseOrder() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useMutation({
    mutationFn: async ({ orderId, payload }: { orderId: number; payload: PaymentPayload }) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return enqueueOrExecute({
        organizationId,
        isConnected,
        method: 'POST',
        path: `/v1/purchase-orders/${orderId}/pay`,
        body: payload,
        onlineFn: () => ordersApi.payPurchaseOrder(orderId, payload, organizationId),
      });
    },
    onSuccess: async (payment) => {
      if (organizationId !== null && payment) {
        await upsertPayments(organizationId, [payment]);
      }

      await queryClient.invalidateQueries({ queryKey: ['purchase-orders'] });
      await queryClient.invalidateQueries({ queryKey: ['purchase-order'] });
      await queryClient.invalidateQueries({ queryKey: ['payments'] });
    },
  });
}

export function useConfirmSalesOrder() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useMutation({
    mutationFn: async (orderId: number) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return enqueueOrExecute({
        organizationId,
        isConnected,
        method: 'POST',
        path: `/v1/sales-orders/${orderId}/confirm`,
        onlineFn: () => ordersApi.confirmSalesOrder(orderId, organizationId),
      });
    },
    onSuccess: async (order) => {
      if (organizationId !== null && order) {
        await upsertSalesOrders(organizationId, [order]);
      }

      await queryClient.invalidateQueries({ queryKey: ['sales-orders'] });
      await queryClient.invalidateQueries({ queryKey: ['sales-order'] });
    },
  });
}

export function useCancelSalesOrder() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useMutation({
    mutationFn: async (orderId: number) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return enqueueOrExecute({
        organizationId,
        isConnected,
        method: 'POST',
        path: `/v1/sales-orders/${orderId}/cancel`,
        onlineFn: () => ordersApi.cancelSalesOrder(orderId, organizationId),
      });
    },
    onSuccess: async (order) => {
      if (organizationId !== null && order) {
        await upsertSalesOrders(organizationId, [order]);
      }

      await queryClient.invalidateQueries({ queryKey: ['sales-orders'] });
      await queryClient.invalidateQueries({ queryKey: ['sales-order'] });
    },
  });
}

export function useFulfillSalesOrder() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useMutation({
    mutationFn: async ({ orderId, payload }: { orderId: number; payload: FulfillSalesOrderPayload }) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return enqueueOrExecute({
        organizationId,
        isConnected,
        method: 'POST',
        path: `/v1/sales-orders/${orderId}/fulfill`,
        body: payload,
        onlineFn: () => ordersApi.fulfillSalesOrder(orderId, payload, organizationId),
      });
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['sales-orders'] });
      await queryClient.invalidateQueries({ queryKey: ['sales-order'] });
      await queryClient.invalidateQueries({ queryKey: ['stocks'] });
      await queryClient.invalidateQueries({ queryKey: ['stock-movements'] });
    },
  });
}

export function useDeliverSalesOrder() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useMutation({
    mutationFn: async (orderId: number) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return enqueueOrExecute({
        organizationId,
        isConnected,
        method: 'POST',
        path: `/v1/sales-orders/${orderId}/deliver`,
        onlineFn: () => ordersApi.deliverSalesOrder(orderId, organizationId),
      });
    },
    onSuccess: async (order) => {
      if (organizationId !== null && order) {
        await upsertSalesOrders(organizationId, [order]);
      }

      await queryClient.invalidateQueries({ queryKey: ['sales-orders'] });
      await queryClient.invalidateQueries({ queryKey: ['sales-order'] });
    },
  });
}

export function usePaySalesOrder() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useMutation({
    mutationFn: async ({ orderId, payload }: { orderId: number; payload: PaymentPayload }) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return enqueueOrExecute({
        organizationId,
        isConnected,
        method: 'POST',
        path: `/v1/sales-orders/${orderId}/pay`,
        body: payload,
        onlineFn: () => ordersApi.paySalesOrder(orderId, payload, organizationId),
      });
    },
    onSuccess: async (payment) => {
      if (organizationId !== null && payment) {
        await upsertPayments(organizationId, [payment]);
      }

      await queryClient.invalidateQueries({ queryKey: ['sales-orders'] });
      await queryClient.invalidateQueries({ queryKey: ['sales-order'] });
      await queryClient.invalidateQueries({ queryKey: ['payments'] });
    },
  });
}

export function useRefundSalesOrder() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useMutation({
    mutationFn: async ({ orderId, payload }: { orderId: number; payload: RefundPayload }) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return enqueueOrExecute({
        organizationId,
        isConnected,
        method: 'POST',
        path: `/v1/sales-orders/${orderId}/refund`,
        body: payload,
        onlineFn: () => ordersApi.refundSalesOrder(orderId, payload, organizationId),
      });
    },
    onSuccess: async (payment) => {
      if (organizationId !== null && payment) {
        await upsertPayments(organizationId, [payment]);
      }

      await queryClient.invalidateQueries({ queryKey: ['sales-orders'] });
      await queryClient.invalidateQueries({ queryKey: ['sales-order'] });
      await queryClient.invalidateQueries({ queryKey: ['payments'] });
    },
  });
}
