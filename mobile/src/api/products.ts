import { apiRequest, apiRequestPaginated } from '@/src/api/client';
import { buildListQuery, fetchAllPages, type ListQueryParams } from '@/src/api/query';
import type { Category, Product, ProductPayload, Unit } from '@/src/api/types';

type ListParams = ListQueryParams & {
  organizationId?: number | null;
};

export async function fetchProducts(params: ListParams = {}): Promise<{
  products: Product[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}> {
  const response = await apiRequestPaginated<Product[]>(
    `/v1/products${buildListQuery(params)}`,
    { organizationId: params.organizationId },
  );

  return {
    products: response.data,
    pagination: response.meta.pagination,
  };
}

export async function fetchProduct(
  productId: number,
  organizationId?: number | null,
): Promise<Product> {
  return apiRequest<Product>(`/v1/products/${productId}`, { organizationId });
}

export async function createProduct(
  payload: ProductPayload,
  organizationId?: number | null,
): Promise<Product> {
  return apiRequest<Product>('/v1/products', {
    method: 'POST',
    body: payload,
    organizationId,
  });
}

export async function updateProduct(
  productId: number,
  payload: Partial<ProductPayload>,
  organizationId?: number | null,
): Promise<Product> {
  return apiRequest<Product>(`/v1/products/${productId}`, {
    method: 'PUT',
    body: payload,
    organizationId,
  });
}

export async function deleteProduct(
  productId: number,
  organizationId?: number | null,
): Promise<void> {
  await apiRequest<void>(`/v1/products/${productId}`, {
    method: 'DELETE',
    organizationId,
  });
}

export async function fetchCategories(params: ListParams = {}): Promise<Category[]> {
  const response = await apiRequestPaginated<Category[]>(
    `/v1/categories${buildListQuery({ ...params, perPage: params.perPage ?? 200 })}`,
    { organizationId: params.organizationId },
  );

  return response.data;
}

export async function fetchUnits(params: ListParams = {}): Promise<Unit[]> {
  const response = await apiRequestPaginated<Unit[]>(
    `/v1/units${buildListQuery({ ...params, perPage: params.perPage ?? 200 })}`,
    { organizationId: params.organizationId },
  );

  return response.data;
}

export async function fetchAllProducts(
  organizationId: number,
  updatedAfter?: string | null,
): Promise<Product[]> {
  return fetchAllPages((page) => fetchProducts({
    page,
    perPage: 100,
    organizationId,
    updatedAfter,
  }).then((response) => ({
    items: response.products,
    lastPage: response.pagination.last_page,
  })));
}
