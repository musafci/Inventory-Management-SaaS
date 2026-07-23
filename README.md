# Inventory Management SaaS

Multi-tenant inventory, purchasing, and sales API built with Laravel 13, PostgreSQL, Redis, Laravel Passport, and Laravel Horizon.

> **Full runbook:** [docs/GETTING-STARTED.md](docs/GETTING-STARTED.md) — Docker & local setup, Horizon, scheduler, Stripe webhooks, email, demo accounts, tests, production checklist, and troubleshooting.

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
| Health | http://localhost:8080/api/health |
| Platform portal | http://localhost:8080/platform/login |
| Platform API | http://localhost:8080/api/platform/v1 |
| OpenAPI docs | http://localhost:8080/docs/api |
| Horizon dashboard | http://localhost:8080/horizon |
| PostgreSQL | `localhost:5433` (user `inventory`, password `secret`, db `inventory`) |
| Redis | `localhost:6379` |

The `app:setup` command runs migrations, seeds roles/permissions, generates Passport keys, and creates a password-grant OAuth client. Use `--write-env` inside Docker so Passport credentials are saved to `.env`.

If login fails with **"These credentials do not match our records"** after `migrate:fresh` or `db:seed`, your `.env` Passport client ID is probably stale. Fix it with:

```bash
php artisan passport:ensure-password-client --write-env
```

Then restart `php artisan serve` if it is running.

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

## Web UI (Livewire frontend)

Run the dev server and use **http://localhost:8000** for every page (login, dashboard, and all routes):

```bash
php artisan serve --host=localhost --port=8000
```

Set `APP_URL=http://localhost:8000` in `.env`. Post-login redirects use relative paths so they stay on the same host and port. Always open **http://localhost:8000/login** — do not use `http://localhost/dashboard` without `:8000` (that hits Apache on port 80).

```bash
php artisan config:clear   # after changing APP_URL
```

Optional queue worker for notifications:

```bash
php artisan horizon
```

> **Apache on port 80:** only needed if you deliberately want `http://localhost/...` without a port. See `deploy/enable-apache.sh` and set `APP_URL=http://localhost` instead.

## Local development (without Docker)

**Prerequisites:** PHP 8.3+, Composer, PostgreSQL 16, Redis 7.

```bash
composer install
cp .env.example .env
# Set DB_HOST=127.0.0.1 and REDIS_HOST=127.0.0.1 in .env for native services

php artisan app:setup --write-env

# Run API + queue worker (or use Horizon)
php artisan serve --host=localhost --port=8000
php artisan horizon

# Scheduler (production: add cron — see GETTING-STARTED.md)
php artisan schedule:work   # local dev: run scheduler in foreground
```

## Running tests

```bash
php artisan test
```

Tests use SQLite in-memory by default. **13 Postgres concurrency tests are skipped** unless you opt in:

```bash
RUN_STOCK_PG_CONCURRENCY=1 php artisan test
```

Pre-launch readiness coverage: `php artisan test --filter=PrelaunchReadiness`

## Backend context (for teammates)

### Architecture

- **Multi-tenant:** Each request to tenant routes must include `X-Organization-Id`. Middleware `ResolveTenant` binds `currentOrganization`, sets Spatie permission team context, and **blocks suspended organizations** (403).
- **Auth:** Laravel Passport password grant. Public routes: `register`, `login`, `refresh`, `forgot-password`, `reset-password`. Protected: `me`, `logout`, `sessions`. Auth endpoints are rate-limited per IP+email (5/15 min). Password reset revokes all existing tokens.
- **Subscriptions:** New orgs receive a **14-day Growth trial** via `organization_subscriptions` (no separate trial plan row). Four tiers: Starter, Growth, Business, Enterprise — see [PRICING_PLAN.md](PRICING_PLAN.md) and [docs/SUBSCRIPTIONS-AND-PLANS.md](docs/SUBSCRIPTIONS-AND-PLANS.md). Plan limits use a graduated grace buffer; expired trials allow reads but block writes (402). Past-due subscriptions allow reads and writes during a configurable grace period, then block writes (402). Cancelled subscriptions are read-only with write block (402).
- **Billing:** Stripe Checkout + Customer Portal at `/api/v1/billing/*`. Webhook at `POST /api/stripe/webhook` with signature verification and idempotent event processing (`stripe_events` table).
- **GDPR:** Owner-only data export (`POST /api/v1/organization/export`) and deferred account deletion (`request-deletion` / `cancel-deletion`).
- **Platform admin:** Separate guard at `/api/platform/v1` and Livewire portal at `/platform/*` — see [docs/PLATFORM-ADMIN.md](docs/PLATFORM-ADMIN.md).
- **API envelope:** Success responses are `{ "data": ..., "meta": ... }`. Errors are `{ "message": "...", "errors": { ... } }`.
- **Stock:** All `quantity_on_hand` changes go through `StockService::recordMovement()`. An observer fires `StockLevelChanged` for low-stock notifications (queued via Horizon).
- **Idempotency:** `POST /api/v1/purchase-orders` and `POST /api/v1/sales-orders` require an `Idempotency-Key` header.
- **Rate limiting:** Tenant routes use `throttle:api-tenant` keyed by `org:{X-Organization-Id}:user:{user_id}`. Login, register, and forgot-password use separate IP+email limiters (`auth-login`, `auth-register`, `auth-forgot-password`).
- **Health & CI:** `GET /api/health` checks DB, Redis (when configured), and queue. GitHub Actions runs Pest on every PR (`.github/workflows/tests.yml`).

**Documentation:**

| Doc | Description |
|-----|-------------|
| [docs/GETTING-STARTED.md](docs/GETTING-STARTED.md) | **How to run the project** — setup, Docker, queues, cron, Stripe, email, tests |
| [docs/SYSTEM-ARCHITECTURE-AND-WORKFLOWS.md](docs/SYSTEM-ARCHITECTURE-AND-WORKFLOWS.md) | Full system architecture & detailed workflows |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Condensed architecture with diagrams |
| [docs/RBAC-PERMISSIONS.md](docs/RBAC-PERMISSIONS.md) | Tenant permission catalog & how to add permissions |
| [docs/SUBSCRIPTIONS-AND-PLANS.md](docs/SUBSCRIPTIONS-AND-PLANS.md) | Plans, subscriptions, limits & enforcement |
| [PRICING_PLAN.md](PRICING_PLAN.md) | Authoritative pricing spec & seed values |
| [docs/PLATFORM-ADMIN.md](docs/PLATFORM-ADMIN.md) | Super-admin portal, impersonation & platform API |
| [docs/IMPERSONATION.md](docs/IMPERSONATION.md) | Login-as-tenant for support debugging (web + API) |
| [docs/POS-PLANNING.md](docs/POS-PLANNING.md) | **Future:** complete POS module planning (not implemented) |
| [PRELAUNCH_READINESS.MD](PRELAUNCH_READINESS.MD) | Pre-launch checklist (L0–L8) — implementation reference |
| [PROJECT_BRIEF_FOR_SUPERADMIN.md](PROJECT_BRIEF_FOR_SUPERADMIN.md) | Platform layer product requirements |

### Main domain areas

| Area | Key paths |
|------|-----------|
| Auth | `app/Services/AuthService.php`, `app/Services/PasswordResetService.php`, `app/Services/SessionService.php`, `routes/api.php` (`auth/*`) |
| Billing / Stripe | `app/Services/StripeBillingService.php`, `app/Http/Controllers/Api/V1/BillingController.php`, `POST /api/stripe/webhook` |
| GDPR | `app/Services/OrganizationDataExportService.php`, `app/Services/OrganizationDeletionService.php` |
| Catalog | Products, categories, units, warehouses |
| Purchasing | Purchase orders → send → receive (`GoodsReceiptService`) → pay |
| Sales | Sales orders → confirm (reservation) → fulfill → deliver → pay / refund |
| Stock | `StockService`, `StockMovementObserver`, `GET/POST stock-movements` |
| Payments | Polymorphic `Payment` on sales/purchase orders |
| Reports | `ReportService` — stock valuation, low stock, sales/purchase summaries |
| Audit | Spatie Activity Log on orders, payments, stock movements |
| Platform admin | Cross-tenant org management, subscriptions, flags, impersonation — `routes/platform.php`, `/platform/*` |

### Platform admin portal

Super-admins manage all tenants separately from organization RBAC:

| URL | Purpose |
|-----|---------|
| `/platform/login` | Platform sign-in |
| `/platform/dashboard` | Tenant overview |
| `/platform/organizations` | Org directory |
| `/platform/organizations/{id}` | Status, subscription, flags, notes, impersonation |
| `/platform/admins` | Platform admin accounts |

```bash
php artisan platform:admin:create platform@example.com "Platform Admin" --password=your-password
```

See [docs/PLATFORM-ADMIN.md](docs/PLATFORM-ADMIN.md) for the full API and enforcement details. For login-as-tenant support debugging, see [docs/IMPERSONATION.md](docs/IMPERSONATION.md).

### Roles (seeded per organization)

`System Owner`, `Org Owner`, `Admin`, `Manager`, `Warehouse Staff`, `Sales Staff`, `Viewer` — permissions defined in `app/Permission/PermissionCatalog.php` and seeded via `RolesAndPermissionsSeeder`. Platform operators use a **separate** `platform_admins` table (not Spatie roles).

### Demo data (local environment)

When `APP_ENV=local`, `php artisan db:seed` also runs `DemoSeeder` with two organizations and a multi-org consultant account:

| Account | Password | Organizations |
|---------|----------|---------------|
| `owner@acme.demo` | `password123` | Acme Warehouse (Owner) |
| `owner@beta.demo` | `password123` | Beta Retail (Owner) |
| `consultant@demo.test` | `password123` | Both (Admin / Manager) |
| `platform@demo.test` | `password123` | Platform admin API + **web portal** at `/platform/login` |

### API documentation

OpenAPI is auto-generated with [Scramble](https://github.com/dedoc/scramble). Browse `/docs/api` locally or export:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan scramble:export
```

### Useful commands

```bash
# Full bootstrap (migrations + seeders + Passport)
php artisan app:setup --write-env

# Seed plans & feature flags only
php artisan db:seed --class=PlanSeeder

# Sync RBAC for existing organizations
php artisan rbac:migrate-organizations

# Backfill subscription rows for legacy organizations
php artisan platform:subscriptions:backfill

# Expire past-due trial subscriptions (also scheduled daily)
php artisan subscriptions:expire-trials

# Send trial-ending-soon emails (also scheduled daily)
php artisan subscriptions:notify-trial-ending

# Process organizations past deletion grace period (also scheduled daily)
php artisan organizations:process-deletions

# Create platform super-admin
php artisan platform:admin:create email@example.com "Admin Name" --password=secret

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
| `API_RATE_LIMIT_PER_MINUTE` | Per org+user API throttle fallback (default 120) |
| `SUBSCRIPTION_TRIAL_DAYS` | Trial length in days (default 14) |
| `SUBSCRIPTION_TRIAL_PLAN_SLUG` | Plan slug assigned on registration (default `growth`) |
| `ORGANIZATION_REGISTRATION_NOTIFICATION_EMAIL` | Platform notification on new org signup (default in `config/organization.php`) |
| `SUBSCRIPTION_TRIAL_ENDING_REMINDER_DAYS` | Days before trial end to send reminder email (default 3) |
| `SUBSCRIPTION_PAST_DUE_GRACE_DAYS` | Write-access grace after payment failure (default 7) |
| `ORGANIZATION_DELETION_GRACE_DAYS` | Grace period before hard-delete after owner requests deletion (default 30) |
| `MAIL_*` | Mail driver — use `ses` in production (see Laravel mail config) |
| `STRIPE_*` | Stripe keys and per-plan price IDs — see `.env.example` |

See `.env.example` for the full list.

## License

MIT
