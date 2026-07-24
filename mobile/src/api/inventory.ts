import { apiRequest, apiRequestPaginated } from '@/src/api/client';
import { buildListQuery, fetchAllPages, type ListQueryParams } from '@/src/api/query';
import type {
  Stock,
  StockMovement,
  StockMovementPayload,
  Warehouse,
} from '@/src/api/types';

type ListParams = ListQueryParams & {
  organizationId?: number | null;
};

function buildQuery(params: ListParams): string {
  return buildListQuery(params);
}

export async function fetchStocks(params: ListParams = {}): Promise<{
  stocks: Stock[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}> {
  const response = await apiRequestPaginated<Stock[]>(
    `/v1/stocks${buildQuery(params)}`,
    { organizationId: params.organizationId },
  );

  return {
    stocks: response.data,
    pagination: response.meta.pagination,
  };
}

export async function fetchStockMovements(params: ListParams = {}): Promise<{
  movements: StockMovement[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}> {
  const response = await apiRequestPaginated<StockMovement[]>(
    `/v1/stock-movements${buildQuery(params)}`,
    { organizationId: params.organizationId },
  );

  return {
    movements: response.data,
    pagination: response.meta.pagination,
  };
}

export type WarehousePayload = {
  name: string;
  address?: string | null;
  is_default?: boolean;
};

export async function fetchWarehouses(params: ListParams = {}): Promise<Warehouse[]> {
  const response = await apiRequestPaginated<Warehouse[]>(
    `/v1/warehouses${buildQuery({ ...params, perPage: params.perPage ?? 200 })}`,
    { organizationId: params.organizationId },
  );

  return response.data;
}

export async function fetchWarehouse(
  warehouseId: number,
  organizationId?: number | null,
): Promise<Warehouse> {
  return apiRequest<Warehouse>(`/v1/warehouses/${warehouseId}`, { organizationId });
}

export async function createWarehouse(
  payload: WarehousePayload,
  organizationId?: number | null,
): Promise<Warehouse> {
  return apiRequest<Warehouse>('/v1/warehouses', {
    method: 'POST',
    body: payload,
    organizationId,
  });
}

export async function updateWarehouse(
  warehouseId: number,
  payload: Partial<WarehousePayload>,
  organizationId?: number | null,
): Promise<Warehouse> {
  return apiRequest<Warehouse>(`/v1/warehouses/${warehouseId}`, {
    method: 'PUT',
    body: payload,
    organizationId,
  });
}

export async function deleteWarehouse(
  warehouseId: number,
  organizationId?: number | null,
): Promise<void> {
  await apiRequest<void>(`/v1/warehouses/${warehouseId}`, {
    method: 'DELETE',
    organizationId,
  });
}

export async function createStockMovement(
  payload: StockMovementPayload,
  organizationId?: number | null,
): Promise<StockMovement> {
  return apiRequest<StockMovement>('/v1/stock-movements', {
    method: 'POST',
    body: payload,
    organizationId,
  });
}

export async function fetchAllStocks(
  organizationId: number,
  updatedAfter?: string | null,
): Promise<Stock[]> {
  return fetchAllPages((page) => fetchStocks({
    page,
    perPage: 100,
    organizationId,
    updatedAfter,
  }).then((response) => ({ items: response.stocks, lastPage: response.pagination.last_page })));
}

export async function fetchAllStockMovements(
  organizationId: number,
  updatedAfter?: string | null,
): Promise<StockMovement[]> {
  return fetchAllPages((page) => fetchStockMovements({
    page,
    perPage: 100,
    organizationId,
    updatedAfter,
  }).then((response) => ({
    items: response.movements,
    lastPage: response.pagination.last_page,
  })));
}
