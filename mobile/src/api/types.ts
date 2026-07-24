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

export type Supplier = {
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

export type Customer = {
  id: number;
  organization_id: number;
  name: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  created_at: string | null;
  updated_at: string | null;
};

export type SupplierPayload = {
  name: string;
  contact_person?: string | null;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
};

export type CustomerPayload = {
  name: string;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
};

export type PurchaseOrderItem = {
  id: number;
  product_id: number;
  quantity_ordered: number;
  quantity_received: number;
  quantity_remaining: number;
  unit_cost: string;
  discount?: string;
  subtotal: string;
};

export type SalesOrderItem = {
  id: number;
  product_id: number;
  quantity: number;
  quantity_fulfilled: number;
  quantity_returned: number;
  unit_price: string;
  discount?: string;
  subtotal: string;
};

export type PurchaseOrder = {
  id: number;
  organization_id: number;
  supplier_id: number;
  warehouse_id: number;
  po_number: string;
  status: string;
  order_date: string;
  expected_date: string | null;
  total_amount: string;
  amount_paid?: string;
  amount_due?: string;
  supplier?: Supplier;
  items?: PurchaseOrderItem[];
  created_at: string | null;
  updated_at: string | null;
};

export type SalesOrder = {
  id: number;
  organization_id: number;
  customer_id: number;
  warehouse_id: number;
  order_number: string;
  status: string;
  order_date: string;
  total_amount: string;
  amount_paid?: string;
  amount_due?: string;
  customer?: Customer;
  items?: SalesOrderItem[];
  created_at: string | null;
  updated_at: string | null;
};

export type PurchaseOrderItemPayload = {
  product_id: number;
  quantity_ordered: number;
  unit_cost: number | string;
  discount?: number | string;
};

export type SalesOrderItemPayload = {
  product_id: number;
  quantity: number;
  unit_price: number | string;
  discount?: number | string;
};

export type PurchaseOrderPayload = {
  supplier_id: number;
  warehouse_id: number;
  order_date: string;
  expected_date?: string | null;
  items: PurchaseOrderItemPayload[];
};

export type SalesOrderPayload = {
  customer_id: number;
  warehouse_id: number;
  order_date: string;
  items: SalesOrderItemPayload[];
};

export type ReceivePurchaseOrderPayload = {
  items: Array<{
    purchase_order_item_id: number;
    quantity: number;
  }>;
  note?: string | null;
};

export type FulfillSalesOrderPayload = {
  items: Array<{
    sales_order_item_id: number;
    quantity: number;
  }>;
  note?: string | null;
};

export type PaymentMethod = 'cash' | 'card' | 'bank_transfer' | 'check' | 'other';

export type Payment = {
  id: number;
  organization_id: number;
  payable_type: string;
  payable_id: number;
  amount: string;
  method: PaymentMethod | string;
  status: string;
  reference: string | null;
  note: string | null;
  paid_at: string | null;
  recorded_by?: number | null;
  created_at: string | null;
  updated_at: string | null;
};

export type PaymentPayload = {
  amount: number | string;
  method: PaymentMethod | string;
  reference?: string | null;
  note?: string | null;
  paid_at?: string | null;
};

export type RefundPayload = {
  amount: number | string;
  method: PaymentMethod | string;
  reference?: string | null;
  note?: string | null;
  paid_at?: string | null;
};

export type DashboardStats = {
  total_products: number;
  total_stock_items: number;
  stock_value: string;
  low_stock_count: number;
  pending_purchase_orders: number;
  pending_sales_orders: number;
};

export type StockValuationReport = {
  total_value: string;
  total_units: number;
  by_warehouse: Array<{
    warehouse_id: number;
    warehouse_name: string;
    total_value: string;
    total_units: number;
  }>;
};

export type LowStockItem = {
  stock_id: number;
  warehouse_id: number;
  warehouse_name: string;
  product_id: number;
  product_name: string;
  sku: string;
  quantity_on_hand: number;
  quantity_reserved: number;
  quantity_available: number;
  reorder_point: number;
};

export type OrderSummaryReport = {
  filters: {
    order_date: { from: string | null; to: string | null };
    payment_date: { from: string | null; to: string | null };
  };
  order_count: number;
  total_amount: string;
  by_status: Array<{
    status: string;
    order_count: number;
    total_amount: string;
  }>;
  payments_received: string;
};

export type ReportExportType =
  | 'stock_valuation'
  | 'low_stock'
  | 'sales_summary'
  | 'purchase_summary';

export type ReportExport = {
  id: number;
  type: string;
  status: string;
  file_path: string | null;
  error_message: string | null;
  completed_at: string | null;
  created_at: string | null;
};

export type OrganizationDetail = {
  id: number;
  name: string;
  slug: string;
  email: string;
  phone: string | null;
  plan: string;
  status: string;
  trial_ends_at: string | null;
  deletion_requested_at: string | null;
  deletion_scheduled_for: string | null;
  users_count?: number;
  role?: string;
};

export type OrganizationPayload = {
  name?: string;
  email?: string;
  phone?: string | null;
};

export type OrganizationMember = {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  status: string;
  role: string | null;
  last_login_at: string | null;
};

export type OrganizationMemberPayload = {
  name: string;
  email: string;
  password: string;
  phone?: string | null;
  role: string;
};

export type Role = {
  id: number;
  name: string;
  description: string | null;
  is_protected: boolean;
  is_system: boolean;
  users_count?: number;
  permissions?: string[];
};

export type RolePayload = {
  name: string;
  description?: string | null;
  permissions: string[];
};

export type PermissionGroups = Record<string, string[]>;

export type Plan = {
  id: number;
  slug: string;
  name: string;
  price_monthly: string;
  price_annual: string;
  limits: Record<string, unknown>;
  is_custom: boolean;
  grace_buffer_percent: number;
  sort_order: number;
  is_active: boolean;
};

export type OrganizationSubscription = {
  id: number;
  organization_id: number;
  plan_id: number;
  status: string;
  trial_ends_at: string | null;
  current_period_ends_at: string | null;
  billing_interval: string | null;
  plan?: Plan;
};

export type BillingOverview = {
  subscription: OrganizationSubscription | null;
  available_plans: Plan[];
  stripe_configured: boolean;
};

export type CheckoutSession = {
  url: string;
};

export type OrganizationDataExport = {
  id: number;
  status: string;
  completed_at: string | null;
  error_message: string | null;
};
