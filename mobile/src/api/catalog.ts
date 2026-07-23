import { apiRequest, apiRequestPaginated } from '@/src/api/client';
import { buildListQuery, fetchAllPages, type ListQueryParams } from '@/src/api/query';
import type { Category, Unit } from '@/src/api/types';

type CatalogListParams = ListQueryParams & {
  organizationId?: number | null;
};

export type CategoryPayload = {
  name: string;
  slug?: string;
  parent_id?: number | null;
};

export type UnitPayload = {
  name: string;
  symbol: string;
};

export async function fetchCategoriesPage(params: CatalogListParams = {}): Promise<{
  categories: Category[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}> {
  const response = await apiRequestPaginated<Category[]>(
    `/v1/categories${buildListQuery(params)}`,
    { organizationId: params.organizationId },
  );

  return {
    categories: response.data,
    pagination: response.meta.pagination,
  };
}

export async function fetchUnitsPage(params: CatalogListParams = {}): Promise<{
  units: Unit[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}> {
  const response = await apiRequestPaginated<Unit[]>(
    `/v1/units${buildListQuery(params)}`,
    { organizationId: params.organizationId },
  );

  return {
    units: response.data,
    pagination: response.meta.pagination,
  };
}

export async function fetchAllCategories(
  organizationId: number,
  updatedAfter?: string | null,
): Promise<Category[]> {
  return fetchAllPages((page) => fetchCategoriesPage({
    page,
    perPage: 100,
    organizationId,
    updatedAfter,
  }).then((response) => ({
    items: response.categories,
    lastPage: response.pagination.last_page,
  })));
}

export async function fetchAllUnits(
  organizationId: number,
  updatedAfter?: string | null,
): Promise<Unit[]> {
  return fetchAllPages((page) => fetchUnitsPage({
    page,
    perPage: 100,
    organizationId,
    updatedAfter,
  }).then((response) => ({
    items: response.units,
    lastPage: response.pagination.last_page,
  })));
}

export async function fetchCategory(
  categoryId: number,
  organizationId?: number | null,
): Promise<Category> {
  return apiRequest<Category>(`/v1/categories/${categoryId}`, { organizationId });
}

export async function createCategory(
  payload: CategoryPayload,
  organizationId?: number | null,
): Promise<Category> {
  return apiRequest<Category>('/v1/categories', {
    method: 'POST',
    body: payload,
    organizationId,
  });
}

export async function updateCategory(
  categoryId: number,
  payload: Partial<CategoryPayload>,
  organizationId?: number | null,
): Promise<Category> {
  return apiRequest<Category>(`/v1/categories/${categoryId}`, {
    method: 'PUT',
    body: payload,
    organizationId,
  });
}

export async function deleteCategory(
  categoryId: number,
  organizationId?: number | null,
): Promise<void> {
  await apiRequest<void>(`/v1/categories/${categoryId}`, {
    method: 'DELETE',
    organizationId,
  });
}

export async function fetchUnit(
  unitId: number,
  organizationId?: number | null,
): Promise<Unit> {
  return apiRequest<Unit>(`/v1/units/${unitId}`, { organizationId });
}

export async function createUnit(
  payload: UnitPayload,
  organizationId?: number | null,
): Promise<Unit> {
  return apiRequest<Unit>('/v1/units', {
    method: 'POST',
    body: payload,
    organizationId,
  });
}

export async function updateUnit(
  unitId: number,
  payload: Partial<UnitPayload>,
  organizationId?: number | null,
): Promise<Unit> {
  return apiRequest<Unit>(`/v1/units/${unitId}`, {
    method: 'PUT',
    body: payload,
    organizationId,
  });
}

export async function deleteUnit(
  unitId: number,
  organizationId?: number | null,
): Promise<void> {
  await apiRequest<void>(`/v1/units/${unitId}`, {
    method: 'DELETE',
    organizationId,
  });
}
