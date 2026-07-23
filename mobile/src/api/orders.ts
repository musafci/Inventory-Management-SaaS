import { apiRequest, apiRequestPaginated } from '@/src/api/client';
import { buildListQuery, fetchAllPages, type ListQueryParams } from '@/src/api/query';
import type {
  FulfillSalesOrderPayload,
  Payment,
  PaymentPayload,
  PurchaseOrder,
  PurchaseOrderPayload,
  ReceivePurchaseOrderPayload,
  RefundPayload,
  SalesOrder,
  SalesOrderPayload,
} from '@/src/api/types';

type ListParams = ListQueryParams & {
  organizationId?: number | null;
};

export async function fetchPurchaseOrders(params: ListParams = {}): Promise<{
  orders: PurchaseOrder[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}> {
  const response = await apiRequestPaginated<PurchaseOrder[]>(
    `/v1/purchase-orders${buildListQuery(params)}`,
    { organizationId: params.organizationId },
  );

  return {
    orders: response.data,
    pagination: response.meta.pagination,
  };
}

export async function fetchSalesOrders(params: ListParams = {}): Promise<{
  orders: SalesOrder[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}> {
  const response = await apiRequestPaginated<SalesOrder[]>(
    `/v1/sales-orders${buildListQuery(params)}`,
    { organizationId: params.organizationId },
  );

  return {
    orders: response.data,
    pagination: response.meta.pagination,
  };
}

export async function fetchPurchaseOrder(
  orderId: number,
  organizationId?: number | null,
): Promise<PurchaseOrder> {
  return apiRequest<PurchaseOrder>(`/v1/purchase-orders/${orderId}`, { organizationId });
}

export async function fetchSalesOrder(
  orderId: number,
  organizationId?: number | null,
): Promise<SalesOrder> {
  return apiRequest<SalesOrder>(`/v1/sales-orders/${orderId}`, { organizationId });
}

export async function createPurchaseOrder(
  payload: PurchaseOrderPayload,
  organizationId?: number | null,
  idempotencyKey?: string,
): Promise<PurchaseOrder> {
  return apiRequest<PurchaseOrder>('/v1/purchase-orders', {
    method: 'POST',
    body: payload,
    organizationId,
    idempotencyKey,
  });
}

export async function createSalesOrder(
  payload: SalesOrderPayload,
  organizationId?: number | null,
  idempotencyKey?: string,
): Promise<SalesOrder> {
  return apiRequest<SalesOrder>('/v1/sales-orders', {
    method: 'POST',
    body: payload,
    organizationId,
    idempotencyKey,
  });
}

export async function updatePurchaseOrder(
  orderId: number,
  payload: Partial<PurchaseOrderPayload>,
  organizationId?: number | null,
): Promise<PurchaseOrder> {
  return apiRequest<PurchaseOrder>(`/v1/purchase-orders/${orderId}`, {
    method: 'PUT',
    body: payload,
    organizationId,
  });
}

export async function updateSalesOrder(
  orderId: number,
  payload: Partial<SalesOrderPayload>,
  organizationId?: number | null,
): Promise<SalesOrder> {
  return apiRequest<SalesOrder>(`/v1/sales-orders/${orderId}`, {
    method: 'PUT',
    body: payload,
    organizationId,
  });
}

export async function deletePurchaseOrder(
  orderId: number,
  organizationId?: number | null,
): Promise<void> {
  await apiRequest<void>(`/v1/purchase-orders/${orderId}`, {
    method: 'DELETE',
    organizationId,
  });
}

export async function deleteSalesOrder(
  orderId: number,
  organizationId?: number | null,
): Promise<void> {
  await apiRequest<void>(`/v1/sales-orders/${orderId}`, {
    method: 'DELETE',
    organizationId,
  });
}

export async function sendPurchaseOrder(
  orderId: number,
  organizationId?: number | null,
): Promise<PurchaseOrder> {
  return apiRequest<PurchaseOrder>(`/v1/purchase-orders/${orderId}/send`, {
    method: 'POST',
    organizationId,
  });
}

export async function cancelPurchaseOrder(
  orderId: number,
  organizationId?: number | null,
): Promise<PurchaseOrder> {
  return apiRequest<PurchaseOrder>(`/v1/purchase-orders/${orderId}/cancel`, {
    method: 'POST',
    organizationId,
  });
}

export async function receivePurchaseOrder(
  orderId: number,
  payload: ReceivePurchaseOrderPayload,
  organizationId?: number | null,
): Promise<unknown> {
  return apiRequest<unknown>(`/v1/purchase-orders/${orderId}/receive`, {
    method: 'POST',
    body: payload,
    organizationId,
  });
}

export async function payPurchaseOrder(
  orderId: number,
  payload: PaymentPayload,
  organizationId?: number | null,
): Promise<Payment> {
  return apiRequest<Payment>(`/v1/purchase-orders/${orderId}/pay`, {
    method: 'POST',
    body: payload,
    organizationId,
  });
}

export async function confirmSalesOrder(
  orderId: number,
  organizationId?: number | null,
): Promise<SalesOrder> {
  return apiRequest<SalesOrder>(`/v1/sales-orders/${orderId}/confirm`, {
    method: 'POST',
    organizationId,
  });
}

export async function cancelSalesOrder(
  orderId: number,
  organizationId?: number | null,
): Promise<SalesOrder> {
  return apiRequest<SalesOrder>(`/v1/sales-orders/${orderId}/cancel`, {
    method: 'POST',
    organizationId,
  });
}

export async function fulfillSalesOrder(
  orderId: number,
  payload: FulfillSalesOrderPayload,
  organizationId?: number | null,
): Promise<unknown> {
  return apiRequest<unknown>(`/v1/sales-orders/${orderId}/fulfill`, {
    method: 'POST',
    body: payload,
    organizationId,
  });
}

export async function deliverSalesOrder(
  orderId: number,
  organizationId?: number | null,
): Promise<SalesOrder> {
  return apiRequest<SalesOrder>(`/v1/sales-orders/${orderId}/deliver`, {
    method: 'POST',
    organizationId,
  });
}

export async function paySalesOrder(
  orderId: number,
  payload: PaymentPayload,
  organizationId?: number | null,
): Promise<Payment> {
  return apiRequest<Payment>(`/v1/sales-orders/${orderId}/pay`, {
    method: 'POST',
    body: payload,
    organizationId,
  });
}

export async function refundSalesOrder(
  orderId: number,
  payload: RefundPayload,
  organizationId?: number | null,
): Promise<Payment> {
  return apiRequest<Payment>(`/v1/sales-orders/${orderId}/refund`, {
    method: 'POST',
    body: payload,
    organizationId,
  });
}

export async function fetchAllPurchaseOrders(
  organizationId: number,
  updatedAfter?: string | null,
): Promise<PurchaseOrder[]> {
  return fetchAllPages((page) => fetchPurchaseOrders({
    page,
    perPage: 100,
    organizationId,
    updatedAfter,
  }).then((response) => ({
    items: response.orders,
    lastPage: response.pagination.last_page,
  })));
}

export async function fetchAllSalesOrders(
  organizationId: number,
  updatedAfter?: string | null,
): Promise<SalesOrder[]> {
  return fetchAllPages((page) => fetchSalesOrders({
    page,
    perPage: 100,
    organizationId,
    updatedAfter,
  }).then((response) => ({
    items: response.orders,
    lastPage: response.pagination.last_page,
  })));
}
