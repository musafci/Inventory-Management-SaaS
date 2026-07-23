import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';

import * as productsApi from '@/src/api/products';
import type { Product, ProductPayload } from '@/src/api/types';
import { useAuth } from '@/src/auth/AuthContext';
import {
  deleteCachedProduct,
  getCachedProduct,
  listCachedProducts,
  upsertProducts,
} from '@/src/db/productsCache';
import { listCachedCategories, listCachedUnits, upsertCategories, upsertUnits } from '@/src/db/catalogCache';
import { useNetwork } from '@/src/network/NetworkContext';

export function useProducts(search: string) {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useInfiniteQuery({
    queryKey: ['products', organizationId, search, isConnected],
    enabled: organizationId !== null,
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      if (organizationId === null) {
        return { products: [], pagination: { current_page: 1, per_page: 0, total: 0, last_page: 1 } };
      }

      if (!isConnected) {
        const products = await listCachedProducts(organizationId, search);

        return {
          products,
          pagination: {
            current_page: 1,
            per_page: products.length,
            total: products.length,
            last_page: 1,
          },
        };
      }

      const response = await productsApi.fetchProducts({
        page: pageParam,
        perPage: 50,
        search,
        organizationId,
      });

      await upsertProducts(organizationId, response.products);

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

export function useProductsList(search: string): Product[] {
  const query = useProducts(search);

  return useMemo(
    () => query.data?.pages.flatMap((page) => page.products) ?? [],
    [query.data?.pages],
  );
}

export function useProduct(productId: number | null) {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useQuery({
    queryKey: ['product', organizationId, productId, isConnected],
    enabled: organizationId !== null && productId !== null,
    queryFn: async () => {
      if (organizationId === null || productId === null) {
        return null;
      }

      if (!isConnected) {
        return getCachedProduct(organizationId, productId);
      }

      const product = await productsApi.fetchProduct(productId, organizationId);
      await upsertProducts(organizationId, [product]);

      return product;
    },
  });
}

export function useCategories() {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useQuery({
    queryKey: ['categories', organizationId, isConnected],
    enabled: organizationId !== null,
    queryFn: async () => {
      if (organizationId === null) {
        return [];
      }

      if (!isConnected) {
        return listCachedCategories(organizationId);
      }

      const categories = await productsApi.fetchCategories({ organizationId, perPage: 200 });
      await upsertCategories(organizationId, categories);

      return categories;
    },
  });
}

export function useUnits() {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useQuery({
    queryKey: ['units', organizationId, isConnected],
    enabled: organizationId !== null,
    queryFn: async () => {
      if (organizationId === null) {
        return [];
      }

      if (!isConnected) {
        return listCachedUnits(organizationId);
      }

      const units = await productsApi.fetchUnits({ organizationId, perPage: 200 });
      await upsertUnits(organizationId, units);

      return units;
    },
  });
}

export function useCreateProduct() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (payload: ProductPayload) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return productsApi.createProduct(payload, organizationId);
    },
    onSuccess: async (product) => {
      if (organizationId !== null) {
        await upsertProducts(organizationId, [product]);
      }

      await queryClient.invalidateQueries({ queryKey: ['products'] });
    },
  });
}

export function useUpdateProduct(productId: number) {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (payload: Partial<ProductPayload>) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return productsApi.updateProduct(productId, payload, organizationId);
    },
    onSuccess: async (product) => {
      if (organizationId !== null) {
        await upsertProducts(organizationId, [product]);
      }

      await queryClient.invalidateQueries({ queryKey: ['products'] });
      await queryClient.invalidateQueries({ queryKey: ['product', organizationId, productId] });
    },
  });
}

export function useDeleteProduct() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (productId: number) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return productsApi.deleteProduct(productId, organizationId);
    },
    onSuccess: async (_result, productId) => {
      if (organizationId !== null) {
        await deleteCachedProduct(organizationId, productId);
      }

      await queryClient.invalidateQueries({ queryKey: ['products'] });
    },
  });
}
