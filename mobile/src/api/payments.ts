import { apiRequest, apiRequestPaginated } from '@/src/api/client';
import { buildListQuery, fetchAllPages, type ListQueryParams } from '@/src/api/query';
import type { Payment } from '@/src/api/types';

type ListParams = ListQueryParams & {
  organizationId?: number | null;
};

export async function fetchPayments(params: ListParams = {}): Promise<{
  payments: Payment[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}> {
  const response = await apiRequestPaginated<Payment[]>(
    `/v1/payments${buildListQuery(params)}`,
    { organizationId: params.organizationId },
  );

  return {
    payments: response.data,
    pagination: response.meta.pagination,
  };
}

export async function fetchPayment(
  paymentId: number,
  organizationId?: number | null,
): Promise<Payment> {
  return apiRequest<Payment>(`/v1/payments/${paymentId}`, { organizationId });
}

export async function fetchAllPayments(
  organizationId: number,
  updatedAfter?: string | null,
): Promise<Payment[]> {
  return fetchAllPages((page) => fetchPayments({
    page,
    perPage: 100,
    organizationId,
    updatedAfter,
  }).then((response) => ({
    items: response.payments,
    lastPage: response.pagination.last_page,
  })));
}
