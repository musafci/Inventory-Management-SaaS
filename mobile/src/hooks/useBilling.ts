import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import * as billingApi from '@/src/api/billing';
import { useAuth } from '@/src/auth/AuthContext';

export function useBillingOverview() {
  const { organizationId } = useAuth();

  return useQuery({
    queryKey: ['billing', organizationId],
    enabled: organizationId !== null,
    queryFn: () => billingApi.fetchBillingOverview(organizationId),
  });
}

export function useCheckoutSession() {
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: ({
      planSlug,
      interval,
    }: {
      planSlug: string;
      interval: 'monthly' | 'annual';
    }) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return billingApi.createCheckoutSession(planSlug, interval, organizationId);
    },
  });
}

export function useBillingPortalSession() {
  const { organizationId } = useAuth();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return billingApi.createBillingPortalSession(organizationId);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['billing'] });
    },
  });
}
