import { apiRequest } from '@/src/api/client';

export type CsvImportResult = {
  imported: number;
  failed: number;
  errors: Array<{
    row: number;
    messages: string[];
  }>;
};

export async function importProducts(
  csv: string,
  organizationId?: number | null,
): Promise<CsvImportResult> {
  return apiRequest<CsvImportResult>('/v1/products/import', {
    method: 'POST',
    body: { csv },
    organizationId,
  });
}

export async function importCustomers(
  csv: string,
  organizationId?: number | null,
): Promise<CsvImportResult> {
  return apiRequest<CsvImportResult>('/v1/customers/import', {
    method: 'POST',
    body: { csv },
    organizationId,
  });
}

export async function importSuppliers(
  csv: string,
  organizationId?: number | null,
): Promise<CsvImportResult> {
  return apiRequest<CsvImportResult>('/v1/suppliers/import', {
    method: 'POST',
    body: { csv },
    organizationId,
  });
}
