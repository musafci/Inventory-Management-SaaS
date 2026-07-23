import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import { useMemo } from 'react';

import * as paymentsApi from '@/src/api/payments';
import { useAuth } from '@/src/auth/AuthContext';
import { getCachedPayment, listCachedPayments, upsertPayments } from '@/src/db/paymentsCache';
import { useNetwork } from '@/src/network/NetworkContext';

export function usePayments() {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useInfiniteQuery({
    queryKey: ['payments', organizationId, isConnected],
    enabled: organizationId !== null,
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      if (organizationId === null) {
        return { payments: [], pagination: { current_page: 1, per_page: 0, total: 0, last_page: 1 } };
      }

      if (!isConnected) {
        const payments = await listCachedPayments(organizationId);

        return {
          payments,
          pagination: {
            current_page: 1,
            per_page: payments.length,
            total: payments.length,
            last_page: 1,
          },
        };
      }

      const response = await paymentsApi.fetchPayments({
        page: pageParam,
        perPage: 50,
        organizationId,
      });

      await upsertPayments(organizationId, response.payments);

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

export function usePaymentsList() {
  const query = usePayments();

  return useMemo(
    () => query.data?.pages.flatMap((page) => page.payments) ?? [],
    [query.data?.pages],
  );
}

export function usePayment(paymentId: number | null) {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useQuery({
    queryKey: ['payment', organizationId, paymentId, isConnected],
    enabled: organizationId !== null && paymentId !== null,
    queryFn: async () => {
      if (organizationId === null || paymentId === null) {
        return null;
      }

      if (!isConnected) {
        return getCachedPayment(organizationId, paymentId);
      }

      const payment = await paymentsApi.fetchPayment(paymentId, organizationId);
      await upsertPayments(organizationId, [payment]);

      return payment;
    },
  });
}
