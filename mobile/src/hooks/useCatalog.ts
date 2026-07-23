import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import * as catalogApi from '@/src/api/catalog';
import type { CategoryPayload, UnitPayload } from '@/src/api/catalog';
import { useAuth } from '@/src/auth/AuthContext';
import {
  deleteCachedCategory,
  deleteCachedUnit,
  listCachedCategories,
  listCachedUnits,
  upsertCategories,
  upsertUnits,
} from '@/src/db/catalogCache';
import { useNetwork } from '@/src/network/NetworkContext';

export function useCategoriesList() {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useQuery({
    queryKey: ['categories-list', organizationId, isConnected],
    enabled: organizationId !== null,
    queryFn: async () => {
      if (organizationId === null) {
        return [];
      }

      if (!isConnected) {
        return listCachedCategories(organizationId);
      }

      const categories = await catalogApi.fetchAllCategories(organizationId);
      await upsertCategories(organizationId, categories);

      return categories;
    },
  });
}

export function useUnitsList() {
  const { organizationId } = useAuth();
  const { isConnected } = useNetwork();

  return useQuery({
    queryKey: ['units-list', organizationId, isConnected],
    enabled: organizationId !== null,
    queryFn: async () => {
      if (organizationId === null) {
        return [];
      }

      if (!isConnected) {
        return listCachedUnits(organizationId);
      }

      const units = await catalogApi.fetchAllUnits(organizationId);
      await upsertUnits(organizationId, units);

      return units;
    },
  });
}

export function useCreateCategory() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (payload: CategoryPayload) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return catalogApi.createCategory(payload, organizationId);
    },
    onSuccess: async (category) => {
      if (organizationId !== null) {
        await upsertCategories(organizationId, [category]);
      }

      await queryClient.invalidateQueries({ queryKey: ['categories'] });
      await queryClient.invalidateQueries({ queryKey: ['categories-list'] });
    },
  });
}

export function useUpdateCategory(categoryId: number) {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (payload: Partial<CategoryPayload>) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return catalogApi.updateCategory(categoryId, payload, organizationId);
    },
    onSuccess: async (category) => {
      if (organizationId !== null) {
        await upsertCategories(organizationId, [category]);
      }

      await queryClient.invalidateQueries({ queryKey: ['categories-list'] });
    },
  });
}

export function useDeleteCategory() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (categoryId: number) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return catalogApi.deleteCategory(categoryId, organizationId);
    },
    onSuccess: async (_result, categoryId) => {
      if (organizationId !== null) {
        await deleteCachedCategory(organizationId, categoryId);
      }

      await queryClient.invalidateQueries({ queryKey: ['categories-list'] });
    },
  });
}

export function useCreateUnit() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (payload: UnitPayload) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return catalogApi.createUnit(payload, organizationId);
    },
    onSuccess: async (unit) => {
      if (organizationId !== null) {
        await upsertUnits(organizationId, [unit]);
      }

      await queryClient.invalidateQueries({ queryKey: ['units'] });
      await queryClient.invalidateQueries({ queryKey: ['units-list'] });
    },
  });
}

export function useUpdateUnit(unitId: number) {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (payload: Partial<UnitPayload>) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return catalogApi.updateUnit(unitId, payload, organizationId);
    },
    onSuccess: async (unit) => {
      if (organizationId !== null) {
        await upsertUnits(organizationId, [unit]);
      }

      await queryClient.invalidateQueries({ queryKey: ['units-list'] });
    },
  });
}

export function useDeleteUnit() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (unitId: number) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return catalogApi.deleteUnit(unitId, organizationId);
    },
    onSuccess: async (_result, unitId) => {
      if (organizationId !== null) {
        await deleteCachedUnit(organizationId, unitId);
      }

      await queryClient.invalidateQueries({ queryKey: ['units-list'] });
    },
  });
}
