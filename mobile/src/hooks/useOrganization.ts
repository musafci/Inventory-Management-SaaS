import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import * as organizationApi from '@/src/api/organization';
import type { OrganizationPayload } from '@/src/api/types';
import { useAuth } from '@/src/auth/AuthContext';

export function useOrganization() {
  const { organizationId } = useAuth();

  return useQuery({
    queryKey: ['organization', organizationId],
    enabled: organizationId !== null,
    queryFn: () => organizationApi.fetchOrganization(organizationId),
  });
}

export function useUpdateOrganization() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (payload: OrganizationPayload) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return organizationApi.updateOrganization(payload, organizationId);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['organization'] });
    },
  });
}

export function useRequestOrganizationDeletion() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: () => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return organizationApi.requestOrganizationDeletion(organizationId);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['organization'] });
    },
  });
}

export function useCancelOrganizationDeletion() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: () => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return organizationApi.cancelOrganizationDeletion(organizationId);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['organization'] });
    },
  });
}

export function useQueueOrganizationExport() {
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: () => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return organizationApi.queueOrganizationExport(organizationId);
    },
  });
}
