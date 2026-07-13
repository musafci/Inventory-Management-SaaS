# Inventory Management SaaS

Multi-tenant inventory, purchasing, and sales API built with Laravel 13, PostgreSQL, Redis, Laravel Passport, and Laravel Horizon.

## Quick start (Docker)

**Prerequisites:** Docker and Docker Compose.

```bash
# 1. Start app, PostgreSQL, Redis, and Horizon
docker compose up -d --build

# 2. Bootstrap database, seeders, and Passport (single command)
docker compose exec app php artisan app:setup --write-env
```

| Service | URL / port |
|---------|------------|
| API | http://localhost:8080/api/v1 |
| OpenAPI docs | http://localhost:8080/docs/api |
| Horizon dashboard | http://localhost:8080/horizon |
| PostgreSQL | `localhost:5433` (user `inventory`, password `secret`, db `inventory`) |
| Redis | `localhost:6379` |

The `app:setup` command runs migrations, seeds roles/permissions, generates Passport keys, and creates a password-grant OAuth client. Use `--write-env` inside Docker so Passport credentials are saved to `.env`.

### Register a first user

```bash
curl -s -X POST http://localhost:8080/api/v1/auth/register \
  -H 'Content-Type: application/json' \
  -d '{
    "organization_name": "Acme Inventory",
    "name": "Jane Owner",
    "email": "jane@acme.test",
    "password": "password123",
    "password_confirmation": "password123"
  }' | jq
```

Save the `access_token` and `organizations[0].id` from the response. All tenant-scoped requests need:

```
Authorization: Bearer <access_token>
X-Organization-Id: <organization_id>
```

## Local development (without Docker)

**Prerequisites:** PHP 8.3+, Composer, PostgreSQL 16, Redis 7.

```bash
composer install
cp .env.example .env
# Set DB_HOST=127.0.0.1 and REDIS_HOST=127.0.0.1 in .env for native services

php artisan app:setup --write-env

# Run API + queue worker (or use Horizon)
php artisan serve
php artisan horizon
```

## Running tests

```bash
php artisan test
```

Tests use SQLite in-memory by default. **13 Postgres concurrency tests are skipped** unless you opt in:

```bash
RUN_STOCK_PG_CONCURRENCY=1 php artisan test
```

## Backend context (for teammates)

### Architecture

- **Multi-tenant:** Each request to tenant routes must include `X-Organization-Id`. Middleware `ResolveTenant` binds `currentOrganization` and sets Spatie permission team context.
- **Auth:** Laravel Passport password grant. Public routes: `POST /api/v1/auth/register`, `login`, `refresh`. Protected routes use `auth:api`.
- **API envelope:** Success responses are `{ "data": ..., "meta": ... }`. Errors are `{ "message": "...", "errors": { ... } }`.
- **Stock:** All `quantity_on_hand` changes go through `StockService::recordMovement()`. An observer fires `StockLevelChanged` for low-stock notifications (queued via Horizon).
- **Idempotency:** `POST /api/v1/purchase-orders` and `POST /api/v1/sales-orders` require an `Idempotency-Key` header.
- **Rate limiting:** Tenant routes use `throttle:api-tenant` keyed by `org:{X-Organization-Id}:user:{user_id}`.

### Main domain areas

| Area | Key paths |
|------|-----------|
| Auth | `app/Services/AuthService.php`, `routes/api.php` (`auth/*`) |
| Catalog | Products, categories, units, warehouses |
| Purchasing | Purchase orders → send → receive (`GoodsReceiptService`) → pay |
| Sales | Sales orders → confirm (reservation) → fulfill → deliver → pay / refund |
| Stock | `StockService`, `StockMovementObserver`, `GET/POST stock-movements` |
| Payments | Polymorphic `Payment` on sales/purchase orders |
| Reports | `ReportService` — stock valuation, low stock, sales/purchase summaries |
| Audit | Spatie Activity Log on orders, payments, stock movements |

### Roles (seeded)

`Org Owner`, `Manager`, `Warehouse Staff`, `Viewer` — permissions defined in `database/seeders/RolesAndPermissionsSeeder.php`.

### API documentation

OpenAPI is auto-generated with [Scramble](https://github.com/dedoc/scramble). Browse `/docs/api` locally or export:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan scramble:export
```

### Useful commands

```bash
# Full bootstrap (migrations + seeders + Passport)
php artisan app:setup --write-env

# Migrations only
php artisan migrate

# Clear config cache after .env changes
php artisan config:clear

# Horizon
php artisan horizon
```

## Docker services

| Service | Role |
|---------|------|
| `app` | Laravel HTTP server (`php artisan serve` on port 8080 → container 8000) |
| `postgres` | Primary database |
| `redis` | Cache, sessions, queues, Horizon metadata |
| `horizon` | Queue worker supervisor (low-stock notifications, etc.) |

On first `docker compose up`, the entrypoint waits for PostgreSQL, copies `.env.example` → `.env` if needed, syncs Docker database/Redis settings into `.env`, and runs `composer install` when `vendor/` is missing. Run `app:setup` once after containers are healthy.

> **Note:** The API is published on host port **8080** (mapped to container port 8000). If you prefer port 8000, change the mapping in `docker-compose.yml`.

## Environment variables

| Variable | Purpose |
|----------|---------|
| `PASSPORT_PASSWORD_CLIENT_ID` | OAuth password-grant client (set by `app:setup`) |
| `PASSPORT_PASSWORD_CLIENT_SECRET` | OAuth client secret (set by `app:setup`) |
| `API_RATE_LIMIT_PER_MINUTE` | Per org+user API throttle (default 120) |

See `.env.example` for the full list.

## License

MIT
