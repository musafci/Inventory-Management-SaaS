import { getApiBaseUrl } from '@/src/api/config';
import { apiRequest, apiRequestPaginated } from '@/src/api/client';
import type {
  DashboardStats,
  LowStockItem,
  OrderSummaryReport,
  ReportExport,
  ReportExportType,
  StockValuationReport,
} from '@/src/api/types';
import * as authStorage from '@/src/auth/storage';

type ReportParams = {
  organizationId?: number | null;
  warehouseId?: number | null;
  orderFrom?: string | null;
  orderTo?: string | null;
  paymentFrom?: string | null;
  paymentTo?: string | null;
};

function buildSummaryQuery(params: ReportParams): string {
  const search = new URLSearchParams();

  if (params.orderFrom) {
    search.set('order_from', params.orderFrom);
  }

  if (params.orderTo) {
    search.set('order_to', params.orderTo);
  }

  if (params.paymentFrom) {
    search.set('payment_from', params.paymentFrom);
  }

  if (params.paymentTo) {
    search.set('payment_to', params.paymentTo);
  }

  const query = search.toString();

  return query.length > 0 ? `?${query}` : '';
}

export async function fetchDashboard(
  organizationId?: number | null,
): Promise<DashboardStats> {
  return apiRequest<DashboardStats>('/v1/reports/dashboard', { organizationId });
}

export async function fetchStockValuation(
  params: ReportParams = {},
): Promise<StockValuationReport> {
  const query = params.warehouseId ? `?warehouse_id=${params.warehouseId}` : '';

  return apiRequest<StockValuationReport>(`/v1/reports/stock-valuation${query}`, {
    organizationId: params.organizationId,
  });
}

export async function fetchLowStock(
  organizationId?: number | null,
  warehouseId?: number | null,
): Promise<LowStockItem[]> {
  const query = warehouseId ? `?warehouse_id=${warehouseId}` : '';

  return apiRequest<LowStockItem[]>(`/v1/reports/low-stock${query}`, { organizationId });
}

export async function fetchSalesSummary(
  params: ReportParams = {},
): Promise<OrderSummaryReport> {
  return apiRequest<OrderSummaryReport>(
    `/v1/reports/sales-summary${buildSummaryQuery(params)}`,
    { organizationId: params.organizationId },
  );
}

export async function fetchPurchaseSummary(
  params: ReportParams = {},
): Promise<OrderSummaryReport> {
  return apiRequest<OrderSummaryReport>(
    `/v1/reports/purchase-summary${buildSummaryQuery(params)}`,
    { organizationId: params.organizationId },
  );
}

export async function fetchReportExports(
  organizationId?: number | null,
): Promise<ReportExport[]> {
  const response = await apiRequestPaginated<ReportExport[]>('/v1/reports/exports', {
    organizationId,
  });

  return response.data;
}

export async function createReportExport(
  type: ReportExportType,
  organizationId?: number | null,
): Promise<ReportExport> {
  return apiRequest<ReportExport>('/v1/reports/exports', {
    method: 'POST',
    body: { type },
    organizationId,
  });
}

export async function fetchReportExport(
  exportId: number,
  organizationId?: number | null,
): Promise<ReportExport> {
  return apiRequest<ReportExport>(`/v1/reports/exports/${exportId}`, {
    organizationId,
  });
}

export async function downloadReportExport(
  exportId: number,
  organizationId?: number | null,
): Promise<string> {
  const headers: Record<string, string> = {
    Accept: 'text/csv',
  };

  const token = await authStorage.getAccessToken();
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const orgId = organizationId ?? (await authStorage.getOrganizationId());
  if (orgId !== null && orgId !== undefined) {
    headers['X-Organization-Id'] = String(orgId);
  }

  const response = await fetch(`${getApiBaseUrl()}/v1/reports/exports/${exportId}/download`, {
    headers,
  });

  if (!response.ok) {
    throw new Error('Failed to download export.');
  }

  return response.text();
}
