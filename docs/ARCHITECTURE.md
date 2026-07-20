# Oneapp — Architecture

Multi-tenant inventory, purchasing, and sales platform built with Laravel 13, PostgreSQL, Redis, Laravel Passport, and Livewire.

---

## 1. System overview

```mermaid
flowchart TB
    subgraph Client["Clients"]
        Browser["Browser (Livewire UI)"]
        APIClient["External API clients / mobile / integrations"]
    end

    subgraph Laravel["Laravel 13 Application"]
        WebRoutes["Web routes<br/>routes/web.php"]
        APIRoutes["API routes<br/>routes/api.php"]
        Livewire["Livewire components<br/>app/Http/Livewire/*"]
        ApiBridge["ApiClient<br/>internal /api sub-requests"]
        Controllers["API Controllers<br/>app/Http/Controllers/Api/V1/*"]
        Services["Domain Services<br/>app/Services/*"]
        Models["Eloquent Models<br/>app/Models/*"]
    end

    subgraph Infra["Infrastructure"]
        PG[(PostgreSQL)]
        Redis[(Redis<br/>cache · session · queue)]
        Horizon["Horizon worker<br/>background jobs"]
    end

    Browser --> WebRoutes
    WebRoutes --> Livewire
    Livewire --> ApiBridge
    ApiBridge --> APIRoutes

    APIClient --> APIRoutes

    APIRoutes --> Controllers
    Controllers --> Services
    Services --> Models
    Models --> PG

    Services --> Redis
    Horizon --> Redis
    Horizon --> PG
```

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.3, Laravel 13 |
| Web UI | Livewire 4, Alpine.js, Tailwind, Vite |
| API auth | Laravel Passport (password grant) |
| Database | PostgreSQL 16 |
| Cache / queues | Redis 7 + Laravel Horizon |
| Permissions | Spatie Permission (per-organization teams) |
| API docs | Scramble OpenAPI at `/docs/api` |

---

## 2. Multi-tenancy

Every business customer is an **Organization**. All inventory data belongs to one org.

```mermaid
flowchart LR
    User["User"]
    Org["Organization"]
    Pivot["organization_user<br/>(role: Org Owner, Manager, etc.)"]

    User --- Pivot
    Pivot --- Org

    Org --> Products
    Org --> Warehouses
    Org --> Stocks
    Org --> PO["Purchase Orders"]
    Org --> SO["Sales Orders"]
    Org --> Payments
```

### How tenant context is set

```mermaid
sequenceDiagram
    participant C as Client
    participant API as API Middleware
    participant RT as ResolveTenant
    participant DB as Database

    C->>API: Request + Bearer token + X-Organization-Id
    API->>API: Passport validates token (auth:api)
    API->>RT: Check org header
    RT->>RT: Verify user belongs to org
    RT->>RT: Bind app('currentOrganization')
    RT->>RT: Set Spatie permission team = org id
    RT->>DB: Queries auto-scoped by organization_id
```

**Rules:**

- Header required on all tenant API routes: `X-Organization-Id: <id>`
- Models use `BelongsToOrganization` + `OrganizationScope` — queries without tenant context return **nothing** (fail-closed)
- Rate limit: per org + per user (`throttle:api-tenant`)

**Key files:** `app/Http/Middleware/ResolveTenant.php`, `app/Traits/BelongsToOrganization.php`, `app/Models/Scopes/OrganizationScope.php`, `app/Permission/OrganizationTeamResolver.php`

---

## 3. Authentication

Web UI and external API share the **same Passport OAuth tokens**.

### API auth (direct)

```mermaid
sequenceDiagram
    participant Client
    participant AuthCtrl as AuthController
    participant AuthSvc as AuthService
    participant OAuth as /oauth/token
    participant DB as PostgreSQL

    Client->>AuthCtrl: POST /api/v1/auth/register or login
    AuthCtrl->>AuthSvc: register() / login()
    AuthSvc->>OAuth: Internal password-grant request
    OAuth-->>AuthSvc: access_token + refresh_token
    AuthSvc->>DB: Create/find user + organization
    AuthSvc-->>Client: { access_token, organizations[] }
```

### Web auth (session wrapper)

```mermaid
sequenceDiagram
    participant Browser
    participant WebAuth as AuthController (Web)
    participant AuthSvc as AuthService
    participant Session
    participant Livewire

    Browser->>WebAuth: POST /login (form)
    WebAuth->>AuthSvc: login() → OAuth token
    AuthSvc-->>WebAuth: access_token + org + user info
    WebAuth->>Session: Store auth_token, organization_id, user_name
    WebAuth-->>Browser: Redirect to /dashboard

    Browser->>Livewire: GET /products (WebAuth middleware)
    Livewire->>Session: Read auth_token + organization_id
    Livewire->>Livewire: ApiClient → internal /api/v1/products
```

**Session keys:** `auth_token`, `organization_id`, `user_name`, `user_email`, `organizations`

**Bootstrap:** `php artisan app:setup --write-env` creates Passport keys + password-grant client.

**Key files:** `app/Services/AuthService.php`, `app/Http/Controllers/Web/AuthController.php`, `app/Http/Middleware/WebAuth.php`, `app/Console/Commands/SetupApplication.php`

---

## 4. Web UI architecture

The web UI does **not** talk to the database directly. Every page is a thin Livewire client over the REST API.

```mermaid
flowchart TB
    subgraph Page["Browser page e.g. /products"]
        LW["Livewire: Products.php"]
        View["Blade: livewire/products/index.blade.php"]
        LW --> View
    end

    subgraph Session["PHP Session"]
        Token["auth_token"]
        OrgId["organization_id"]
    end

    subgraph Bridge["ApiClient"]
        SubReq["Request::create('/api/v1/...')"]
        Handle["app()->handle() → full API stack"]
    end

    subgraph API["Same API as external clients"]
        Passport["auth:api"]
        Tenant["ResolveTenant"]
        Controller["ProductController"]
        Service["ProductService"]
    end

    LW --> Session
    LW --> Bridge
    SubReq --> Handle
    Handle --> Passport --> Tenant --> Controller --> Service
```

### User action example — Add Product

```mermaid
sequenceDiagram
    participant User
    participant LW as Livewire Products
    participant AC as ApiClient
    participant API as POST /api/v1/products

    User->>LW: Click "Add Product" → openModal()
    User->>LW: Fill form → store()
    LW->>LW: Validate form
    LW->>AC: post('/v1/products', form data)
    AC->>API: Internal request + Bearer + X-Organization-Id
    API-->>AC: { data: product }
    AC-->>LW: Success
    LW->>LW: closeModal(), reload list, show toast
```

**Livewire pages:** Dashboard, Products, Categories, Units, Warehouses, Suppliers, Customers, Purchase Orders, Sales Orders, Stocks, Stock Movements, Payments, Reports.

**Key files:** `routes/web.php`, `app/Http/Livewire/*`, `app/Services/Web/ApiClient.php`, `resources/views/layouts/app.blade.php`

> **Note:** `ApiClient` and `AuthService` both use `app()->handle()` for internal sub-requests. The original HTTP request must be restored afterward so Livewire, URL generation, and redirects keep the correct web path.

---

## 5. API request pipeline

```mermaid
flowchart LR
    HTTP["HTTP Request"] --> Auth["auth:api<br/>Passport"]
    Auth --> Tenant["tenant<br/>ResolveTenant"]
    Tenant --> Throttle["throttle:api-tenant"]
    Throttle --> Idem{"Idempotency?<br/>PO/SO create"}
    Idem --> Policy["Gate / Policy"]
    Policy --> Request["Form Request<br/>validation"]
    Request --> Service["Domain Service"]
    Service --> Model["Scoped Eloquent"]
    Model --> Resource["API Resource"]
    Resource --> JSON["{ data, meta }"]
```

**Response envelope:**

- Success: `{ "data": ..., "meta": { "pagination": ... } }`
- Error: `{ "message": "...", "errors": { ... } }`

**Required headers for tenant routes:**

```
Authorization: Bearer <access_token>
X-Organization-Id: <organization_id>
Idempotency-Key: <uuid>   # required for POST purchase-orders / sales-orders
```

**Key files:** `routes/api.php`, `app/Http/Controllers/Api/V1/*`, `app/Http/Resources/*`, `app/Http/Middleware/EnforceIdempotency.php`

---

## 6. Domain model

```mermaid
erDiagram
    Organization ||--o{ User : "organization_user"
    Organization ||--o{ Product : has
    Organization ||--o{ Warehouse : has
    Organization ||--o{ Supplier : has
    Organization ||--o{ Customer : has

    Product }o--|| Category : belongs_to
    Product }o--|| Unit : belongs_to

    Warehouse ||--o{ Stock : holds
    Product ||--o{ Stock : tracked_in
    Stock {
        int quantity_on_hand
        int quantity_reserved
    }

    Supplier ||--o{ PurchaseOrder : places
    PurchaseOrder ||--|{ PurchaseOrderItem : contains
    PurchaseOrder ||--o{ GoodsReceipt : receives_via
    GoodsReceipt ||--|{ GoodsReceiptItem : contains

    Customer ||--o{ SalesOrder : places
    SalesOrder ||--|{ SalesOrderItem : contains
    SalesOrder ||--o{ SalesFulfillment : ships_via
    SalesFulfillment ||--|{ SalesFulfillmentItem : contains

    PurchaseOrder ||--o{ Payment : payable
    SalesOrder ||--o{ Payment : payable

    Stock ||--o{ StockMovement : ledger
    StockMovement }o--o| GoodsReceipt : source
    StockMovement }o--o| SalesFulfillment : source
    StockMovement }o--o| Payment : source
```

**Scoped models:** Product, Category, Unit, Warehouse, Supplier, Customer, Stock, StockMovement, PurchaseOrder, SalesOrder, Payment, GoodsReceipt, SalesFulfillment, IdempotencyKey.

---

## 7. Purchase order lifecycle

```mermaid
stateDiagram-v2
    [*] --> draft : Create PO
    draft --> sent : send()
    draft --> cancelled : cancel()
    sent --> partially_received : receive() partial
    sent --> received : receive() full
    sent --> cancelled : cancel()
    partially_received --> received : receive() remaining
    partially_received --> partially_received : receive() more
    received --> [*]
    cancelled --> [*]

    note right of partially_received
        Record payment allowed
        when partially_received or received
    end note
```

| Step | Service | Stock impact |
|------|---------|--------------|
| Create / edit / delete | `PurchaseOrderService` | None |
| **Send** | `PurchaseOrderService::send()` | None (order sent to supplier) |
| **Receive goods** | `GoodsReceiptService::receive()` | `purchase_in` stock movements |
| **Pay** | `PaymentService::recordPurchasePayment()` | None (financial record) |

```mermaid
sequenceDiagram
    participant M as Manager
    participant PO as PurchaseOrderService
    participant GR as GoodsReceiptService
    participant SS as StockService
    participant DB as stock_movements + stocks

    M->>PO: POST /purchase-orders (draft)
    M->>PO: POST /purchase-orders/{id}/send
    M->>GR: POST /purchase-orders/{id}/receive
    GR->>SS: recordMovement(purchase_in)
    SS->>DB: Insert movement → observer updates quantity_on_hand
    M->>PO: POST /purchase-orders/{id}/pay
```

**Key files:** `app/Enums/PurchaseOrderStatus.php`, `app/Services/PurchaseOrderService.php`, `app/Services/GoodsReceiptService.php`

---

## 8. Sales order lifecycle

```mermaid
stateDiagram-v2
    [*] --> draft : Create SO
    draft --> confirmed : confirm() reserves stock
    draft --> cancelled : cancel()
    confirmed --> shipped : fulfill() ships goods
    confirmed --> cancelled : cancel() releases reservation
    shipped --> delivered : deliver()
    shipped --> refunded : refund() optional restock
    delivered --> refunded : refund() optional restock
    cancelled --> [*]
    refunded --> [*]
    delivered --> [*]
```

| Step | Service | Stock impact |
|------|---------|--------------|
| **Confirm** | `SalesOrderService::confirm()` | `quantity_reserved` ↑ |
| **Fulfill / ship** | `SalesOrderFulfillmentService::fulfill()` | Reservation consumed → `sale_out` movement |
| **Cancel** (confirmed) | `SalesOrderService::cancel()` | Reservation released |
| **Deliver** | `SalesOrderService::deliver()` | None (status only) |
| **Pay** | `PaymentService::recordSalesPayment()` | None |
| **Refund** | `PaymentService::recordSalesRefund()` | Optional `return_in` restock |

```mermaid
sequenceDiagram
    participant S as Sales Staff
    participant SO as SalesOrderService
    participant SF as SalesOrderFulfillmentService
    participant SS as StockService

    S->>SO: POST /sales-orders (draft)
    S->>SO: POST /sales-orders/{id}/confirm
    SO->>SS: reserveQuantity()
    S->>SF: POST /sales-orders/{id}/fulfill
    SF->>SS: fulfillFromReservation() + sale_out
    S->>SO: POST /sales-orders/{id}/deliver
    S->>SO: POST /sales-orders/{id}/pay
```

**Key files:** `app/Enums/SalesOrderStatus.php`, `app/Services/SalesOrderService.php`, `app/Services/SalesOrderFulfillmentService.php`, `app/Services/PaymentService.php`

---

## 9. Stock ledger

**All** changes to `quantity_on_hand` go through one path:

```mermaid
flowchart TB
    Action["Business action<br/>(receive, fulfill, adjust, transfer, refund)"]
    SS["StockService::recordMovement()"]
    SM["Insert stock_movements row"]
    Obs["StockMovementObserver"]
    Stock["Update stocks.quantity_on_hand"]
    Event["Fire StockLevelChanged event"]
    Listener["CheckLowStock listener"]
    Job["SendLowStockNotificationJob"]
    Horizon["Horizon queue worker"]
    Notify["LowStockNotification → Org Owner + Manager"]

    Action --> SS --> SM --> Obs --> Stock --> Event --> Listener
    Listener -->|"qty ≤ reorder_point"| Job --> Horizon --> Notify
```

### Movement types

| Type | Direction | Typical source |
|------|-----------|----------------|
| `purchase_in` | In | Goods receipt |
| `sale_out` | Out | Sales fulfillment |
| `adjustment_in/out` | In/Out | Manual adjustment API |
| `transfer_in/out` | In/Out | Warehouse transfer |
| `return_in/out` | In/Out | Sales refund restock |

**Concurrency:** row locks + canonical product lock ordering prevent race conditions on stock updates.

**Key files:** `app/Services/StockService.php`, `app/Observers/StockMovementObserver.php`, `app/Enums/StockMovementType.php`, `app/Listeners/CheckLowStock.php`, `app/Jobs/SendLowStockNotificationJob.php`

---

## 10. Reports and audit

```mermaid
flowchart LR
    subgraph Reports["ReportService"]
        SV["Stock Valuation<br/>qty × cost_price"]
        LS["Low Stock<br/>qty ≤ reorder_point"]
        SS["Sales Summary<br/>by status + payments"]
        PS["Purchase Summary<br/>by status + payments"]
    end

    subgraph Audit["Activity Log"]
        PO["PurchaseOrder changes"]
        SO["SalesOrder changes"]
        Pay["Payment changes"]
        Mov["StockMovement changes"]
    end

    Reports --> API["GET /api/v1/reports/*"]
    Audit --> DB[(activity_log table)]
```

**Key files:** `app/Services/ReportService.php`, `app/Http/Controllers/Api/V1/ReportController.php`, `app/Traits/LogsModelActivity.php`

---

## 11. Deployment

```mermaid
flowchart TB
    subgraph Docker["docker compose up"]
        App["app :8080<br/>php artisan serve"]
        PG["postgres :5433"]
        Redis["redis :6379"]
        Hor["horizon<br/>queue worker"]
    end

    Setup["php artisan app:setup --write-env"]
    Setup --> Migrate["migrate"]
    Setup --> Seed["RolesAndPermissionsSeeder"]
    Setup --> Passport["Passport keys + OAuth client"]

    App --> PG
    App --> Redis
    Hor --> Redis
    Hor --> PG
```

### URLs

| What | URL |
|------|-----|
| Web UI (local dev) | `http://localhost:8000` |
| API (Docker) | `http://localhost:8080/api/v1` |
| API docs | `/docs/api` |
| Horizon | `/horizon` |

### Bootstrap

```bash
# Docker
docker compose up -d --build
docker compose exec app php artisan app:setup --write-env

# Local (no Docker)
composer install && cp .env.example .env
php artisan app:setup --write-env
php artisan serve --host=localhost --port=8000
php artisan horizon
```

---

## 12. End-to-end user journey

```mermaid
flowchart TB
    A["1. Register organization + owner"] --> B["2. Login to Web UI"]
    B --> C["3. Set up catalog<br/>Categories · Units · Products"]
    C --> D["4. Set up warehouses + suppliers + customers"]
    D --> E{"Operations"}

    E --> F["Purchasing flow<br/>PO → Send → Receive → Pay"]
    E --> G["Sales flow<br/>SO → Confirm → Fulfill → Deliver → Pay"]
    E --> H["Stock adjustments / transfers"]
    E --> I["Reports & low-stock alerts"]

    F --> J["Stock increases (purchase_in)"]
    G --> K["Stock reserved then decreased (sale_out)"]
    H --> L["Manual movements"]
    J --> M["Dashboard + Reports reflect changes"]
    K --> M
    L --> M
    I --> M
```

---

## 13. Roles and permissions

Seeded roles (per organization via Spatie teams):

| Role | Typical access |
|------|----------------|
| Org Owner | Full access |
| Manager | Manage catalog, orders, reports |
| Warehouse Staff | Stock, receipts, fulfillments |
| Sales Staff | Customers, sales orders |
| Viewer | Read-only |

**Key file:** `database/seeders/RolesAndPermissionsSeeder.php`

---

## 14. Key files quick reference

| Area | Path |
|------|------|
| Web routes | `routes/web.php` |
| API routes | `routes/api.php` |
| Livewire UI | `app/Http/Livewire/*` |
| API bridge | `app/Services/Web/ApiClient.php` |
| Auth | `app/Services/AuthService.php`, `app/Http/Controllers/Web/AuthController.php` |
| Tenant middleware | `app/Http/Middleware/ResolveTenant.php` |
| Stock core | `app/Services/StockService.php`, `app/Observers/StockMovementObserver.php` |
| Purchase orders | `app/Services/PurchaseOrderService.php`, `app/Services/GoodsReceiptService.php` |
| Sales orders | `app/Services/SalesOrderService.php`, `app/Services/SalesOrderFulfillmentService.php` |
| Payments | `app/Services/PaymentService.php` |
| Reports | `app/Services/ReportService.php` |
| Docker | `docker-compose.yml`, `Dockerfile`, `docker/entrypoint.sh` |
| Setup command | `app/Console/Commands/SetupApplication.php` |
| Web session / token refresh | `app/Services/Web/WebSessionService.php`, `app/Http/Middleware/WebAuth.php` |

---

## 15. Web UI ↔ API coverage

The Livewire frontend consumes the REST API through `ApiClient` (internal sub-requests with Bearer token + `X-Organization-Id`).

| API area | Web coverage |
|----------|--------------|
| Auth register/login | Yes — `AuthController` → `AuthService` (session stores tokens) |
| Auth refresh | Yes — `WebSessionService::refreshIfNeeded()` in `WebAuth` + `ApiClient` |
| Auth logout | Yes — `POST /api/v1/auth/logout` + web session clear |
| Auth me | Covered via session user data at login |
| Organization switch | Yes — `POST /organization/switch` updates session `organization_id` |
| Team members (`/api/v1/users`) | Yes — `/users` Livewire page (invite, role update, remove) |
| Products, categories, units, warehouses, suppliers, customers | Full CRUD |
| Purchase orders | Full lifecycle + detail page at `/purchase-orders/{id}` |
| Sales orders | Full lifecycle + detail page at `/sales-orders/{id}` |
| Stocks | Index only (matches API) |
| Stock movements | Index + create |
| Payments | Index + detail page at `/payments/{id}` |
| Reports | All report endpoints + dashboard aggregates + CSV export queue |
| Platform admin API | `/api/platform/v1/*` — separate `platform` guard (not wired to web UI) |
| `POST products/authorization-probe` | API/test only — not used in web UI |

**Idempotency:** `ApiClient` automatically sends `Idempotency-Key` for `POST /v1/purchase-orders` and `POST /v1/sales-orders`.

---

## Summary

InvenTrack is a **multi-tenant Laravel SaaS** where the **Livewire web UI** calls the same **Passport-protected REST API** that external clients use. All data is scoped per **Organization**, and **stock is always changed through a movement ledger** that drives reservations, purchase receipts, sales fulfillments, and low-stock notifications.
