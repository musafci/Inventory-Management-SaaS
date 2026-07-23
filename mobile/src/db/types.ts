export type OutboxStatus = 'pending' | 'processing' | 'failed';

export type OutboxMutation = {
  id: number;
  organization_id: number;
  method: string;
  path: string;
  body: string | null;
  idempotency_key: string | null;
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
  | 'stock_movements';

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
