export type AuthToken = {
  access_token: string;
  refresh_token: string;
  expires_in: number;
  token_type: string;
};

export type Organization = {
  id: number;
  name: string;
  slug: string;
  email: string | null;
  phone: string | null;
  plan: string;
  status: string;
  trial_ends_at: string | null;
  role?: string;
};

export type User = {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  status: string;
  default_organization_id: number | null;
  last_login_at: string | null;
};

export type ImpersonationSession = {
  active: boolean;
  platform_admin_name?: string;
  reason?: string;
  organization_id?: number;
  started_at?: string;
} | null;

export type LoginResponse = {
  user: User;
  organizations: Organization[];
  token: AuthToken;
};

export type MeResponse = {
  user: User;
  organizations: Organization[];
  active_organization_id: number | null;
  permissions: string[];
  impersonation: ImpersonationSession;
};

export type PaginationMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type ApiEnvelope<T> = {
  data: T;
  meta?: {
    pagination?: PaginationMeta;
    [key: string]: unknown;
  };
};

export type PaginatedResponse<T> = {
  data: T;
  meta: {
    pagination: PaginationMeta;
  };
};

export type ApiErrorBody = {
  message: string;
  errors?: Record<string, string[]>;
};

export type Product = {
  id: number;
  organization_id: number;
  category_id: number;
  unit_id: number;
  name: string;
  sku: string | null;
  barcode: string | null;
  cost_price: string;
  selling_price: string;
  tax_rate: string;
  reorder_point: number | null;
  is_active: boolean;
  created_at: string | null;
  updated_at: string | null;
};

export type Category = {
  id: number;
  organization_id: number;
  parent_id: number | null;
  name: string;
  slug: string;
  created_at: string | null;
  updated_at: string | null;
};

export type Unit = {
  id: number;
  organization_id: number;
  name: string;
  symbol: string;
  created_at: string | null;
  updated_at: string | null;
};

export type ProductPayload = {
  category_id: number;
  unit_id: number;
  name: string;
  sku?: string | null;
  barcode?: string | null;
  cost_price?: number | string;
  selling_price?: number | string;
  tax_rate?: number | string;
  reorder_point?: number | null;
  is_active?: boolean;
};

export type Warehouse = {
  id: number;
  organization_id: number;
  name: string;
  address: string | null;
  is_default: boolean;
  created_at: string | null;
  updated_at: string | null;
};

export type Stock = {
  id: number;
  organization_id: number;
  warehouse_id: number;
  product_id: number;
  quantity_on_hand: number;
  quantity_reserved: number;
  quantity_available: number;
  last_counted_at: string | null;
  created_at: string | null;
  updated_at: string | null;
};

export type StockMovementType = 'adjustment_in' | 'adjustment_out';

export type StockMovement = {
  id: number;
  organization_id: number;
  warehouse_id: number;
  product_id: number;
  type: string;
  quantity: number;
  note: string | null;
  reference_type: string | null;
  reference_id: number | null;
  created_by: number | null;
  created_at: string | null;
  updated_at: string | null;
};

export type StockMovementPayload = {
  warehouse_id: number;
  product_id: number;
  type: StockMovementType;
  quantity: number;
  note?: string | null;
};
