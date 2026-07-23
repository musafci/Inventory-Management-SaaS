import { apiRequest, apiRequestPaginated } from '@/src/api/client';
import { buildListQuery, fetchAllPages, type ListQueryParams } from '@/src/api/query';
import type { Customer, CustomerPayload, Supplier, SupplierPayload } from '@/src/api/types';

type ListParams = ListQueryParams & {
  organizationId?: number | null;
};

export async function fetchSuppliers(params: ListParams = {}): Promise<{
  suppliers: Supplier[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}> {
  const response = await apiRequestPaginated<Supplier[]>(
    `/v1/suppliers${buildListQuery(params)}`,
    { organizationId: params.organizationId },
  );

  return {
    suppliers: response.data,
    pagination: response.meta.pagination,
  };
}

export async function fetchCustomers(params: ListParams = {}): Promise<{
  customers: Customer[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}> {
  const response = await apiRequestPaginated<Customer[]>(
    `/v1/customers${buildListQuery(params)}`,
    { organizationId: params.organizationId },
  );

  return {
    customers: response.data,
    pagination: response.meta.pagination,
  };
}

export async function fetchSupplier(
  supplierId: number,
  organizationId?: number | null,
): Promise<Supplier> {
  return apiRequest<Supplier>(`/v1/suppliers/${supplierId}`, { organizationId });
}

export async function fetchCustomer(
  customerId: number,
  organizationId?: number | null,
): Promise<Customer> {
  return apiRequest<Customer>(`/v1/customers/${customerId}`, { organizationId });
}

export async function createSupplier(
  payload: SupplierPayload,
  organizationId?: number | null,
): Promise<Supplier> {
  return apiRequest<Supplier>('/v1/suppliers', {
    method: 'POST',
    body: payload,
    organizationId,
  });
}

export async function createCustomer(
  payload: CustomerPayload,
  organizationId?: number | null,
): Promise<Customer> {
  return apiRequest<Customer>('/v1/customers', {
    method: 'POST',
    body: payload,
    organizationId,
  });
}

export async function updateSupplier(
  supplierId: number,
  payload: Partial<SupplierPayload>,
  organizationId?: number | null,
): Promise<Supplier> {
  return apiRequest<Supplier>(`/v1/suppliers/${supplierId}`, {
    method: 'PUT',
    body: payload,
    organizationId,
  });
}

export async function updateCustomer(
  customerId: number,
  payload: Partial<CustomerPayload>,
  organizationId?: number | null,
): Promise<Customer> {
  return apiRequest<Customer>(`/v1/customers/${customerId}`, {
    method: 'PUT',
    body: payload,
    organizationId,
  });
}

export async function deleteSupplier(
  supplierId: number,
  organizationId?: number | null,
): Promise<void> {
  await apiRequest<void>(`/v1/suppliers/${supplierId}`, {
    method: 'DELETE',
    organizationId,
  });
}

export async function deleteCustomer(
  customerId: number,
  organizationId?: number | null,
): Promise<void> {
  await apiRequest<void>(`/v1/customers/${customerId}`, {
    method: 'DELETE',
    organizationId,
  });
}

export async function fetchAllSuppliers(
  organizationId: number,
  updatedAfter?: string | null,
): Promise<Supplier[]> {
  return fetchAllPages((page) => fetchSuppliers({
    page,
    perPage: 100,
    organizationId,
    updatedAfter,
  }).then((response) => ({
    items: response.suppliers,
    lastPage: response.pagination.last_page,
  })));
}

export async function fetchAllCustomers(
  organizationId: number,
  updatedAfter?: string | null,
): Promise<Customer[]> {
  return fetchAllPages((page) => fetchCustomers({
    page,
    perPage: 100,
    organizationId,
    updatedAfter,
  }).then((response) => ({
    items: response.customers,
    lastPage: response.pagination.last_page,
  })));
}
