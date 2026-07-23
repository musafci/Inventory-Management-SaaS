import type { Customer, Supplier } from '@/src/api/types';
import { webPartnersStore } from '@/src/db/memoryStore.web';

export async function upsertSuppliers(organizationId: number, suppliers: Supplier[]): Promise<void> {
  for (const supplier of suppliers) {
    webPartnersStore.suppliers.set(
      webPartnersStore.supplierKey(organizationId, supplier.id),
      { ...supplier, organization_id: organizationId },
    );
  }
}

export async function upsertCustomers(organizationId: number, customers: Customer[]): Promise<void> {
  for (const customer of customers) {
    webPartnersStore.customers.set(
      webPartnersStore.customerKey(organizationId, customer.id),
      { ...customer, organization_id: organizationId },
    );
  }
}

export async function listCachedSuppliers(organizationId: number, search?: string): Promise<Supplier[]> {
  const prefix = `${organizationId}:`;
  let suppliers = [...webPartnersStore.suppliers.entries()]
    .filter(([key]) => key.startsWith(prefix))
    .map(([, supplier]) => supplier)
    .sort((a, b) => a.name.localeCompare(b.name));

  if (search?.trim()) {
    const term = search.trim().toLowerCase();
    suppliers = suppliers.filter((supplier) =>
      supplier.name.toLowerCase().includes(term)
      || supplier.email?.toLowerCase().includes(term)
      || supplier.phone?.toLowerCase().includes(term),
    );
  }

  return suppliers;
}

export async function listCachedCustomers(organizationId: number, search?: string): Promise<Customer[]> {
  const prefix = `${organizationId}:`;
  let customers = [...webPartnersStore.customers.entries()]
    .filter(([key]) => key.startsWith(prefix))
    .map(([, customer]) => customer)
    .sort((a, b) => a.name.localeCompare(b.name));

  if (search?.trim()) {
    const term = search.trim().toLowerCase();
    customers = customers.filter((customer) =>
      customer.name.toLowerCase().includes(term)
      || customer.email?.toLowerCase().includes(term)
      || customer.phone?.toLowerCase().includes(term),
    );
  }

  return customers;
}

export async function getCachedSupplier(
  organizationId: number,
  supplierId: number,
): Promise<Supplier | null> {
  return webPartnersStore.suppliers.get(webPartnersStore.supplierKey(organizationId, supplierId)) ?? null;
}

export async function getCachedCustomer(
  organizationId: number,
  customerId: number,
): Promise<Customer | null> {
  return webPartnersStore.customers.get(webPartnersStore.customerKey(organizationId, customerId)) ?? null;
}

export async function deleteCachedSupplier(organizationId: number, supplierId: number): Promise<void> {
  webPartnersStore.suppliers.delete(webPartnersStore.supplierKey(organizationId, supplierId));
}

export async function deleteCachedCustomer(organizationId: number, customerId: number): Promise<void> {
  webPartnersStore.customers.delete(webPartnersStore.customerKey(organizationId, customerId));
}
