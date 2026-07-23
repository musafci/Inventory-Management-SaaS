import { fetchAllCustomers, fetchAllSuppliers } from '@/src/api/partners';
import { upsertCustomers, upsertSuppliers } from '@/src/db/partnersCache';
import { getSyncCursor, setSyncCursor } from '@/src/db/syncMetadata';

export async function pullPartnersCache(organizationId: number): Promise<void> {
  const [suppliersCursor, customersCursor] = await Promise.all([
    getSyncCursor(organizationId, 'suppliers'),
    getSyncCursor(organizationId, 'customers'),
  ]);

  const [suppliers, customers] = await Promise.all([
    fetchAllSuppliers(organizationId, suppliersCursor),
    fetchAllCustomers(organizationId, customersCursor),
  ]);

  await Promise.all([
    upsertSuppliers(organizationId, suppliers),
    upsertCustomers(organizationId, customers),
  ]);

  const syncedAt = new Date().toISOString();
  await Promise.all([
    setSyncCursor(organizationId, 'suppliers', syncedAt),
    setSyncCursor(organizationId, 'customers', syncedAt),
  ]);
}
