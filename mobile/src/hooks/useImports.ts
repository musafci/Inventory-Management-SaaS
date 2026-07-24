import { useMutation, useQueryClient } from '@tanstack/react-query';

import * as importsApi from '@/src/api/imports';
import { useAuth } from '@/src/auth/AuthContext';
import { useNetwork } from '@/src/network/NetworkContext';

export function useImportProducts() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useMutation({
    mutationFn: (csv: string) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      if (!isConnected) {
        throw new Error('CSV import requires an internet connection.');
      }

      return importsApi.importProducts(csv, organizationId);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['products'] });
    },
  });
}

export function useImportCustomers() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useMutation({
    mutationFn: (csv: string) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      if (!isConnected) {
        throw new Error('CSV import requires an internet connection.');
      }

      return importsApi.importCustomers(csv, organizationId);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['customers'] });
    },
  });
}

export function useImportSuppliers() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useMutation({
    mutationFn: (csv: string) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      if (!isConnected) {
        throw new Error('CSV import requires an internet connection.');
      }

      return importsApi.importSuppliers(csv, organizationId);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['suppliers'] });
    },
  });
}
