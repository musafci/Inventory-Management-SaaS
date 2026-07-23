import type { Customer, Supplier } from '@/src/api/types';
import { getDatabase } from '@/src/db/database.native';
import type { CustomerRow, SupplierRow } from '@/src/db/types';

export async function upsertSuppliers(organizationId: number, suppliers: Supplier[]): Promise<void> {
  if (suppliers.length === 0) {
    return;
  }

  const db = await getDatabase();

  await db.withTransactionAsync(async () => {
    for (const supplier of suppliers) {
      await db.runAsync(
        `INSERT INTO suppliers (
          id, organization_id, name, contact_person, email, phone, address, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (id, organization_id) DO UPDATE SET
          name = excluded.name,
          contact_person = excluded.contact_person,
          email = excluded.email,
          phone = excluded.phone,
          address = excluded.address,
          created_at = excluded.created_at,
          updated_at = excluded.updated_at`,
        supplier.id,
        organizationId,
        supplier.name,
        supplier.contact_person,
        supplier.email,
        supplier.phone,
        supplier.address,
        supplier.created_at,
        supplier.updated_at,
      );
    }
  });
}

export async function upsertCustomers(organizationId: number, customers: Customer[]): Promise<void> {
  if (customers.length === 0) {
    return;
  }

  const db = await getDatabase();

  await db.withTransactionAsync(async () => {
    for (const customer of customers) {
      await db.runAsync(
        `INSERT INTO customers (
          id, organization_id, name, email, phone, address, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (id, organization_id) DO UPDATE SET
          name = excluded.name,
          email = excluded.email,
          phone = excluded.phone,
          address = excluded.address,
          created_at = excluded.created_at,
          updated_at = excluded.updated_at`,
        customer.id,
        organizationId,
        customer.name,
        customer.email,
        customer.phone,
        customer.address,
        customer.created_at,
        customer.updated_at,
      );
    }
  });
}

function rowToSupplier(row: SupplierRow): Supplier {
  return {
    id: row.id,
    organization_id: row.organization_id,
    name: row.name,
    contact_person: row.contact_person,
    email: row.email,
    phone: row.phone,
    address: row.address,
    created_at: row.created_at,
    updated_at: row.updated_at,
  };
}

function rowToCustomer(row: CustomerRow): Customer {
  return {
    id: row.id,
    organization_id: row.organization_id,
    name: row.name,
    email: row.email,
    phone: row.phone,
    address: row.address,
    created_at: row.created_at,
    updated_at: row.updated_at,
  };
}

export async function listCachedSuppliers(organizationId: number, search?: string): Promise<Supplier[]> {
  const db = await getDatabase();
  const rows = await db.getAllAsync<SupplierRow>(
    'SELECT * FROM suppliers WHERE organization_id = ? ORDER BY name COLLATE NOCASE ASC',
    organizationId,
  );

  let suppliers = rows.map(rowToSupplier);

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
  const db = await getDatabase();
  const rows = await db.getAllAsync<CustomerRow>(
    'SELECT * FROM customers WHERE organization_id = ? ORDER BY name COLLATE NOCASE ASC',
    organizationId,
  );

  let customers = rows.map(rowToCustomer);

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
  const db = await getDatabase();
  const row = await db.getFirstAsync<SupplierRow>(
    'SELECT * FROM suppliers WHERE organization_id = ? AND id = ?',
    organizationId,
    supplierId,
  );

  return row ? rowToSupplier(row) : null;
}

export async function getCachedCustomer(
  organizationId: number,
  customerId: number,
): Promise<Customer | null> {
  const db = await getDatabase();
  const row = await db.getFirstAsync<CustomerRow>(
    'SELECT * FROM customers WHERE organization_id = ? AND id = ?',
    organizationId,
    customerId,
  );

  return row ? rowToCustomer(row) : null;
}

export async function deleteCachedSupplier(organizationId: number, supplierId: number): Promise<void> {
  const db = await getDatabase();
  await db.runAsync(
    'DELETE FROM suppliers WHERE organization_id = ? AND id = ?',
    organizationId,
    supplierId,
  );
}

export async function deleteCachedCustomer(organizationId: number, customerId: number): Promise<void> {
  const db = await getDatabase();
  await db.runAsync(
    'DELETE FROM customers WHERE organization_id = ? AND id = ?',
    organizationId,
    customerId,
  );
}
