import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';

import * as inventoryApi from '@/src/api/inventory';
import type { StockMovementPayload } from '@/src/api/types';
import { useAuth } from '@/src/auth/AuthContext';
import {
  listCachedStockMovements,
  listCachedStocks,
  listCachedWarehouses,
  upsertStockMovements,
  upsertStocks,
  upsertWarehouses,
} from '@/src/db/inventoryCache';
import { listCachedProducts } from '@/src/db/productsCache';
import { enqueueMutation } from '@/src/db/outbox';
import { useNetwork } from '@/src/network/NetworkContext';

export function useStocks(search: string) {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useInfiniteQuery({
    queryKey: ['stocks', organizationId, search, isConnected],
    enabled: organizationId !== null,
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      if (organizationId === null) {
        return { stocks: [], pagination: { current_page: 1, per_page: 0, total: 0, last_page: 1 } };
      }

      if (!isConnected) {
        const stocks = await listCachedStocks(organizationId, search);

        return {
          stocks,
          pagination: {
            current_page: 1,
            per_page: stocks.length,
            total: stocks.length,
            last_page: 1,
          },
        };
      }

      const response = await inventoryApi.fetchStocks({
        page: pageParam,
        perPage: 50,
        search,
        organizationId,
      });

      await upsertStocks(organizationId, response.stocks);

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

export function useStocksList(search: string) {
  const query = useStocks(search);

  return useMemo(
    () => query.data?.pages.flatMap((page) => page.stocks) ?? [],
    [query.data?.pages],
  );
}

export function useStockMovements() {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useInfiniteQuery({
    queryKey: ['stock-movements', organizationId, isConnected],
    enabled: organizationId !== null,
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      if (organizationId === null) {
        return {
          movements: [],
          pagination: { current_page: 1, per_page: 0, total: 0, last_page: 1 },
        };
      }

      if (!isConnected) {
        const movements = await listCachedStockMovements(organizationId);

        return {
          movements,
          pagination: {
            current_page: 1,
            per_page: movements.length,
            total: movements.length,
            last_page: 1,
          },
        };
      }

      const response = await inventoryApi.fetchStockMovements({
        page: pageParam,
        perPage: 50,
        organizationId,
      });

      await upsertStockMovements(organizationId, response.movements);

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

export function useStockMovementsList() {
  const query = useStockMovements();

  return useMemo(
    () => query.data?.pages.flatMap((page) => page.movements) ?? [],
    [query.data?.pages],
  );
}

export function useWarehouses() {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useQuery({
    queryKey: ['warehouses', organizationId, isConnected],
    enabled: organizationId !== null,
    queryFn: async () => {
      if (organizationId === null) {
        return [];
      }

      if (!isConnected) {
        return listCachedWarehouses(organizationId);
      }

      const warehouses = await inventoryApi.fetchWarehouses({ organizationId, perPage: 200 });
      await upsertWarehouses(organizationId, warehouses);

      return warehouses;
    },
  });
}

export function useCachedProductsForPicker(search: string) {
  const { organizationId } = useAuth();

  return useQuery({
    queryKey: ['products-picker', organizationId, search],
    enabled: organizationId !== null,
    queryFn: async () => {
      if (organizationId === null) {
        return [];
      }

      return listCachedProducts(organizationId, search);
    },
  });
}

export function useCreateStockMovement() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useMutation({
    mutationFn: async (payload: StockMovementPayload) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      if (!isConnected) {
        await enqueueMutation({
          organizationId,
          method: 'POST',
          path: '/v1/stock-movements',
          body: payload,
        });

        return null;
      }

      return inventoryApi.createStockMovement(payload, organizationId);
    },
    onSuccess: async (movement) => {
      if (organizationId !== null && movement) {
        await upsertStockMovements(organizationId, [movement]);
      }

      await queryClient.invalidateQueries({ queryKey: ['stocks'] });
      await queryClient.invalidateQueries({ queryKey: ['stock-movements'] });
    },
  });
}

export function useInventoryLabels() {
  const { organizationId } = useAuth();

  return useQuery({
    queryKey: ['inventory-labels', organizationId],
    enabled: organizationId !== null,
    queryFn: async () => {
      if (organizationId === null) {
        return { products: new Map<number, string>(), warehouses: new Map<number, string>() };
      }

      const [products, warehouses] = await Promise.all([
        listCachedProducts(organizationId),
        listCachedWarehouses(organizationId),
      ]);

      return {
        products: new Map(products.map((product) => [product.id, product.name])),
        warehouses: new Map(warehouses.map((warehouse) => [warehouse.id, warehouse.name])),
      };
    },
  });
}
