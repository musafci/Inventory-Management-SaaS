export type OutboxStatus = 'pending' | 'processing' | 'failed';

export type OutboxMutation = {
  id: number;
  organization_id: number;
  method: string;
  path: string;
  body: string | null;
  idempotency_key: string | null;
  depends_on_id: number | null;
  status: OutboxStatus;
  error_message: string | null;
  created_at: string;
  updated_at: string;
};

export type SyncResource =
  | 'products'
  | 'categories'
  | 'units'
  | 'warehouses'
  | 'stocks'
  | 'stock_movements'
  | 'suppliers'
  | 'customers'
  | 'purchase_orders'
  | 'sales_orders'
  | 'payments';

export type OrderType = 'purchase_order' | 'sales_order';

export type ProductRow = {
  id: number;
  organization_id: number;
  category_id: number | null;
  unit_id: number | null;
  name: string;
  sku: string | null;
  barcode: string | null;
  cost_price: string | null;
  selling_price: string | null;
  tax_rate: string | null;
  reorder_point: number | null;
  is_active: number;
  created_at: string | null;
  updated_at: string | null;
};

export type CategoryRow = {
  id: number;
  organization_id: number;
  parent_id: number | null;
  name: string;
  slug: string | null;
  created_at: string | null;
  updated_at: string | null;
};

export type UnitRow = {
  id: number;
  organization_id: number;
  name: string;
  symbol: string | null;
  created_at: string | null;
  updated_at: string | null;
};

export type SupplierRow = {
  id: number;
  organization_id: number;
  name: string;
  contact_person: string | null;
  email: string | null;
  phone: string | null;
  address: string | null;
  created_at: string | null;
  updated_at: string | null;
};

export type CustomerRow = {
  id: number;
  organization_id: number;
  name: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  created_at: string | null;
  updated_at: string | null;
};

export type CachedOrderRow = {
  id: number;
  organization_id: number;
  order_type: OrderType;
  status: string;
  reference_number: string | null;
  total_amount: string | null;
  partner_id: number | null;
  order_date: string | null;
  payload: string;
  updated_at: string | null;
};

export type PaymentRow = {
  id: number;
  organization_id: number;
  payable_type: string;
  payable_id: number;
  amount: string;
  method: string;
  status: string;
  reference: string | null;
  note: string | null;
  paid_at: string | null;
  payload: string;
  updated_at: string | null;
};
