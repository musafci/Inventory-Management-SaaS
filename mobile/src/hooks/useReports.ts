import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import * as reportsApi from '@/src/api/reports';
import type { ReportExportType } from '@/src/api/types';
import { useAuth } from '@/src/auth/AuthContext';

export function useDashboard() {
  const { organizationId } = useAuth();

  return useQuery({
    queryKey: ['dashboard', organizationId],
    enabled: organizationId !== null,
    queryFn: () => reportsApi.fetchDashboard(organizationId),
  });
}

export function useStockValuation(warehouseId?: number | null) {
  const { organizationId } = useAuth();

  return useQuery({
    queryKey: ['report-stock-valuation', organizationId, warehouseId],
    enabled: organizationId !== null,
    queryFn: () => reportsApi.fetchStockValuation({
      organizationId,
      warehouseId: warehouseId ?? undefined,
    }),
  });
}

export function useLowStockReport(warehouseId?: number | null) {
  const { organizationId } = useAuth();

  return useQuery({
    queryKey: ['report-low-stock', organizationId, warehouseId],
    enabled: organizationId !== null,
    queryFn: () => reportsApi.fetchLowStock(organizationId, warehouseId ?? undefined),
  });
}

export function useSalesSummary(filters: {
  orderFrom?: string;
  orderTo?: string;
  paymentFrom?: string;
  paymentTo?: string;
}) {
  const { organizationId } = useAuth();

  return useQuery({
    queryKey: ['report-sales-summary', organizationId, filters],
    enabled: organizationId !== null,
    queryFn: () => reportsApi.fetchSalesSummary({
      organizationId,
      ...filters,
    }),
  });
}

export function usePurchaseSummary(filters: {
  orderFrom?: string;
  orderTo?: string;
  paymentFrom?: string;
  paymentTo?: string;
}) {
  const { organizationId } = useAuth();

  return useQuery({
    queryKey: ['report-purchase-summary', organizationId, filters],
    enabled: organizationId !== null,
    queryFn: () => reportsApi.fetchPurchaseSummary({
      organizationId,
      ...filters,
    }),
  });
}

export function useReportExports() {
  const { organizationId } = useAuth();

  return useQuery({
    queryKey: ['report-exports', organizationId],
    enabled: organizationId !== null,
    queryFn: () => reportsApi.fetchReportExports(organizationId),
  });
}

export function useCreateReportExport() {
  const queryClient = useQueryClient();
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: (type: ReportExportType) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return reportsApi.createReportExport(type, organizationId);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['report-exports'] });
    },
  });
}

export async function pollReportExport(
  exportId: number,
  organizationId: number,
  maxAttempts = 20,
): Promise<import('@/src/api/types').ReportExport> {
  for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
    const exportRecord = await reportsApi.fetchReportExport(exportId, organizationId);

    if (exportRecord.status === 'completed' || exportRecord.status === 'failed') {
      return exportRecord;
    }

    await new Promise((resolve) => {
      setTimeout(resolve, 1500);
    });
  }

  throw new Error('Export timed out.');
}

export function useDownloadReportExport() {
  const { organizationId } = useAuth();

  return useMutation({
    mutationFn: async (exportId: number) => {
      if (organizationId === null) {
        throw new Error('No active organization.');
      }

      return reportsApi.downloadReportExport(exportId, organizationId);
    },
  });
}
