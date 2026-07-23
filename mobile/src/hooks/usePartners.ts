import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';

import * as partnersApi from '@/src/api/partners';
import type { CustomerPayload, SupplierPayload } from '@/src/api/types';
import { useAuth } from '@/src/auth/AuthContext';
import {
  deleteCachedCustomer,
  deleteCachedSupplier,
  getCachedCustomer,
  getCachedSupplier,
  listCachedCustomers,
  listCachedSuppliers,
  upsertCustomers,
  upsertSuppliers,
} from '@/src/db/partnersCache';
import { useNetwork } from '@/src/network/NetworkContext';

export function useSuppliers(search: string) {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useInfiniteQuery({
    queryKey: ['suppliers', organizationId, search, isConnected],
    enabled: organizationId !== null,
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      if (organizationId === null) {
        return { suppliers: [], pagination: { current_page: 1, per_page: 0, total: 0, last_page: 1 } };
      }

      if (!isConnected) {
        const suppliers = await listCachedSuppliers(organizationId, search);

        return {
          suppliers,
          pagination: {
            current_page: 1,
            per_page: suppliers.length,
            total: suppliers.length,
            last_page: 1,
          },
        };
      }

      const response = await partnersApi.fetchSuppliers({
        page: pageParam,
        perPage: 50,
        search,
        organizationId,
      });

      await upsertSuppliers(organizationId, response.suppliers);

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

export function useSuppliersList(search: string) {
  const query = useSuppliers(search);

  return useMemo(
    () => query.data?.pages.flatMap((page) => page.suppliers) ?? [],
    [query.data?.pages],
  );
}

export function useCustomers(search: string) {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useInfiniteQuery({
    queryKey: ['customers', organizationId, search, isConnected],
    enabled: organizationId !== null,
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      if (organizationId === null) {
        return { customers: [], pagination: { current_page: 1, per_page: 0, total: 0, last_page: 1 } };
      }

      if (!isConnected) {
        const customers = await listCachedCustomers(organizationId, search);

        return {
          customers,
          pagination: {
            current_page: 1,
            per_page: customers.length,
            total: customers.length,
            last_page: 1,
          },
        };
      }

      const response = await partnersApi.fetchCustomers({
        page: pageParam,
        perPage: 50,
        search,
        organizationId,
      });

      await upsertCustomers(organizationId, response.customers);

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

export function useCustomersList(search: string) {
  const query = useCustomers(search);

  return useMemo(
    () => query.data?.pages.flatMap((page) => page.customers) ?? [],
    [query.data?.pages],
  );
}

export function useSupplier(supplierId: number | null) {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useQuery({
    queryKey: ['supplier', organizationId, supplierId, isConnected],
    enabled: organizationId !== null && supplierId !== null,
    queryFn: async () => {
      if (organizationId === null || supplierId === null) {
        return null;
      }

      if (!isConnected) {
        return getCachedSupplier(organizationId, supplierId);
      }

      const supplier = await partnersApi.fetchSupplier(supplierId, organizationId);
      await upsertSuppliers(organizationId, [supplier]);

      return supplier;
    },
  });
}

export function useCustomer(customerId: number | null) {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useQuery({
    queryKey: ['customer', organizationId, customerId, isConnected],
    enabled: organizationId !== null && customerId !== null,
    queryFn: async () => {
      if (organizationId === null || customerId === null) {
        return null;
      }

      if (!isConnected) {
        return getCachedCustomer(organizationId, customerId);
      }

      const customer = await partnersApi.fetchCustomer(customerId, organizationId);
      await upsertCustomers(organizationId, [customer]);

      return customer;
    },
  });
}

export function useCreateSupplier() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (payload: SupplierPayload) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return partnersApi.createSupplier(payload, organizationId);
    },
    onSuccess: async (supplier) => {
      if (organizationId !== null) {
        await upsertSuppliers(organizationId, [supplier]);
      }

      await queryClient.invalidateQueries({ queryKey: ['suppliers'] });
    },
  });
}

export function useUpdateSupplier(supplierId: number) {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (payload: Partial<SupplierPayload>) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return partnersApi.updateSupplier(supplierId, payload, organizationId);
    },
    onSuccess: async (supplier) => {
      if (organizationId !== null) {
        await upsertSuppliers(organizationId, [supplier]);
      }

      await queryClient.invalidateQueries({ queryKey: ['suppliers'] });
      await queryClient.invalidateQueries({ queryKey: ['supplier', organizationId, supplierId] });
    },
  });
}

export function useDeleteSupplier() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (supplierId: number) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return partnersApi.deleteSupplier(supplierId, organizationId);
    },
    onSuccess: async (_result, supplierId) => {
      if (organizationId !== null) {
        await deleteCachedSupplier(organizationId, supplierId);
      }

      await queryClient.invalidateQueries({ queryKey: ['suppliers'] });
    },
  });
}

export function useCreateCustomer() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (payload: CustomerPayload) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return partnersApi.createCustomer(payload, organizationId);
    },
    onSuccess: async (customer) => {
      if (organizationId !== null) {
        await upsertCustomers(organizationId, [customer]);
      }

      await queryClient.invalidateQueries({ queryKey: ['customers'] });
    },
  });
}

export function useUpdateCustomer(customerId: number) {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (payload: Partial<CustomerPayload>) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return partnersApi.updateCustomer(customerId, payload, organizationId);
    },
    onSuccess: async (customer) => {
      if (organizationId !== null) {
        await upsertCustomers(organizationId, [customer]);
      }

      await queryClient.invalidateQueries({ queryKey: ['customers'] });
      await queryClient.invalidateQueries({ queryKey: ['customer', organizationId, customerId] });
    },
  });
}

export function useDeleteCustomer() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (customerId: number) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return partnersApi.deleteCustomer(customerId, organizationId);
    },
    onSuccess: async (_result, customerId) => {
      if (organizationId !== null) {
        await deleteCachedCustomer(organizationId, customerId);
      }

      await queryClient.invalidateQueries({ queryKey: ['customers'] });
    },
  });
}
