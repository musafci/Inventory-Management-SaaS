export const SCHEMA_VERSION = 2;

export const MIGRATION_SQL = `
CREATE TABLE IF NOT EXISTS schema_version (
  version INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS sync_metadata (
  organization_id INTEGER NOT NULL,
  resource TEXT NOT NULL,
  cursor TEXT,
  synced_at TEXT,
  PRIMARY KEY (organization_id, resource)
);

CREATE TABLE IF NOT EXISTS outbox_mutations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  organization_id INTEGER NOT NULL,
  method TEXT NOT NULL,
  path TEXT NOT NULL,
  body TEXT,
  idempotency_key TEXT,
  status TEXT NOT NULL DEFAULT 'pending',
  error_message TEXT,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS products (
  id INTEGER NOT NULL,
  organization_id INTEGER NOT NULL,
  category_id INTEGER,
  unit_id INTEGER,
  name TEXT NOT NULL,
  sku TEXT,
  barcode TEXT,
  cost_price TEXT,
  selling_price TEXT,
  tax_rate TEXT,
  reorder_point INTEGER,
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT,
  updated_at TEXT,
  PRIMARY KEY (id, organization_id)
);

CREATE TABLE IF NOT EXISTS categories (
  id INTEGER NOT NULL,
  organization_id INTEGER NOT NULL,
  parent_id INTEGER,
  name TEXT NOT NULL,
  slug TEXT,
  created_at TEXT,
  updated_at TEXT,
  PRIMARY KEY (id, organization_id)
);

CREATE TABLE IF NOT EXISTS units (
  id INTEGER NOT NULL,
  organization_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  symbol TEXT,
  created_at TEXT,
  updated_at TEXT,
  PRIMARY KEY (id, organization_id)
);

CREATE TABLE IF NOT EXISTS warehouses (
  id INTEGER NOT NULL,
  organization_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  address TEXT,
  is_default INTEGER NOT NULL DEFAULT 0,
  created_at TEXT,
  updated_at TEXT,
  PRIMARY KEY (id, organization_id)
);

CREATE TABLE IF NOT EXISTS stocks (
  id INTEGER NOT NULL,
  organization_id INTEGER NOT NULL,
  warehouse_id INTEGER NOT NULL,
  product_id INTEGER NOT NULL,
  quantity_on_hand INTEGER NOT NULL DEFAULT 0,
  quantity_reserved INTEGER NOT NULL DEFAULT 0,
  quantity_available INTEGER NOT NULL DEFAULT 0,
  last_counted_at TEXT,
  created_at TEXT,
  updated_at TEXT,
  PRIMARY KEY (id, organization_id)
);

CREATE TABLE IF NOT EXISTS stock_movements (
  id INTEGER NOT NULL,
  organization_id INTEGER NOT NULL,
  warehouse_id INTEGER NOT NULL,
  product_id INTEGER NOT NULL,
  type TEXT NOT NULL,
  quantity INTEGER NOT NULL,
  note TEXT,
  reference_type TEXT,
  reference_id INTEGER,
  created_by INTEGER,
  created_at TEXT,
  updated_at TEXT,
  PRIMARY KEY (id, organization_id)
);

CREATE INDEX IF NOT EXISTS idx_products_org ON products (organization_id);
CREATE INDEX IF NOT EXISTS idx_categories_org ON categories (organization_id);
CREATE INDEX IF NOT EXISTS idx_units_org ON units (organization_id);
CREATE INDEX IF NOT EXISTS idx_warehouses_org ON warehouses (organization_id);
CREATE INDEX IF NOT EXISTS idx_stocks_org ON stocks (organization_id);
CREATE INDEX IF NOT EXISTS idx_stock_movements_org ON stock_movements (organization_id);
CREATE INDEX IF NOT EXISTS idx_outbox_status ON outbox_mutations (status, organization_id);
`;
