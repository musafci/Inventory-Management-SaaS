# Getting Started — Run the Full Project

This is the **complete runbook** for local development, Docker, background jobs, billing, email, tests, and production checklist. For architecture detail, see [SYSTEM-ARCHITECTURE-AND-WORKFLOWS.md](./SYSTEM-ARCHITECTURE-AND-WORKFLOWS.md).

| Document | When to read it |
|----------|-----------------|
| **This file** | First time setup, daily dev workflow, troubleshooting |
| [README.md](../README.md) | Quick reference & doc index |
| [SUBSCRIPTIONS-AND-PLANS.md](./SUBSCRIPTIONS-AND-PLANS.md) | Plans, Stripe, trial/dunning behavior |
| [PLATFORM-ADMIN.md](./PLATFORM-ADMIN.md) | Super-admin portal & platform API |
| [PRELAUNCH_READINESS.MD](../PRELAUNCH_READINESS.MD) | Pre-launch feature checklist |

---

## 1. What you are running

| Component | Purpose |
|-----------|---------|
| **Laravel 13 API** | `/api/v1/*` — tenant REST API (Passport OAuth2) |
| **Platform API** | `/api/platform/v1/*` — super-admin API (separate guard) |
| **Livewire web UI** | `/login`, `/dashboard`, `/settings/*` — business portal |
| **Platform portal** | `/platform/*` — cross-tenant operator UI |
| **PostgreSQL** | Primary database |
| **Redis** | Cache, sessions, queues |
| **Horizon** | Queue workers (email, exports, notifications) |
| **Scheduler** | Daily trial expiry, trial reminders, GDPR deletions |

**Minimum to use the API:** app + PostgreSQL + Passport setup.

**Minimum for full product behavior:** above + Redis + Horizon (queues) + scheduler (cron).

**Minimum for billing in dev:** Stripe test keys + webhook forwarding (see §8).

---

## 2. Prerequisites

| Tool | Version | Required for |
|------|---------|--------------|
| PHP | 8.3+ | Native local dev |
| Composer | 2.x | PHP dependencies |
| Node.js | 18+ | Frontend assets (`npm run build` / `dev`) |
| PostgreSQL | 16 | Database |
| Redis | 7 | Queues, cache, sessions (recommended) |
| Docker + Compose | Current | Docker workflow (optional) |

---

## 3. Option A — Docker (recommended for first run)

### 3.1 Start everything

```bash
cd /var/www/Inventory-Management-SaaS

# Build and start app, postgres, redis, horizon
docker compose up -d --build

# One-time bootstrap: migrate, seed, Passport keys, OAuth client
docker compose exec app php artisan app:setup --write-env
```

`app:setup` runs:

1. `key:generate` (if `APP_KEY` empty)
2. `migrate --force`
3. `db:seed --force` (roles, permissions, plans, demo data when `APP_ENV=local`)
4. `passport:keys` (if missing)
5. `passport:ensure-password-client --write-env`

### 3.2 URLs (Docker)

| Service | URL |
|---------|-----|
| API | http://localhost:8080/api/v1 |
| Health | http://localhost:8080/api/health |
| Web UI | http://localhost:8080/login |
| Platform portal | http://localhost:8080/platform/login |
| OpenAPI docs | http://localhost:8080/docs/api |
| Horizon | http://localhost:8080/horizon |
| PostgreSQL | `localhost:5433` (user `inventory`, password `secret`, db `inventory`) |
| Redis | `localhost:6379` |

Docker sets `APP_URL=http://localhost:8080` in the container environment.

### 3.3 Frontend assets (Docker)

If CSS/JS looks unstyled, build assets once on the host (volume-mounted into the container):

```bash
npm install
npm run build
```

For active frontend development:

```bash
npm run dev
```

### 3.4 Common Docker commands

```bash
# Logs
docker compose logs -f app horizon

# Shell inside app container
docker compose exec app bash

# Re-run migrations from scratch (destroys data)
docker compose exec app php artisan migrate:fresh --seed --force
docker compose exec app php artisan passport:ensure-password-client --write-env

# Stop
docker compose down

# Stop and remove database volume
docker compose down -v
```

---

## 4. Option B — Local development (no Docker)

### 4.1 Install & configure

```bash
cd /var/www/Inventory-Management-SaaS

composer install
cp .env.example .env

# Edit .env for native services:
#   DB_HOST=127.0.0.1
#   DB_PORT=5432        (or your Postgres port)
#   REDIS_HOST=127.0.0.1
#   APP_URL=http://localhost:8000

php artisan app:setup --write-env
```

Ensure PostgreSQL and Redis are running locally before `app:setup`.

### 4.2 Run processes (three terminals)

**Terminal 1 — HTTP server**

```bash
php artisan serve --host=localhost --port=8000
```

**Terminal 2 — Queue worker**

```bash
php artisan horizon
```

**Terminal 3 — Frontend (optional during UI work)**

```bash
npm install
npm run dev
```

Or build once: `npm run build`

### 4.3 URLs (local)

| Service | URL |
|---------|-----|
| Web UI | http://localhost:8000/login |
| API | http://localhost:8000/api/v1 |
| Health | http://localhost:8000/api/health |
| Platform portal | http://localhost:8000/platform/login |
| Horizon | http://localhost:8000/horizon |

Always use the **same host and port** as `APP_URL`. After changing `APP_URL`:

```bash
php artisan config:clear
```

---

## 5. Scheduler (required for trials & GDPR)

These commands are registered in `routes/console.php` and must run **once per day** in production:

| Command | Purpose |
|---------|---------|
| `subscriptions:expire-trials` | Mark expired trials |
| `subscriptions:notify-trial-ending` | Trial-ending reminder emails |
| `organizations:process-deletions` | Hard-delete orgs past deletion grace |

**Local testing (run manually):**

```bash
php artisan subscriptions:expire-trials
php artisan subscriptions:notify-trial-ending
php artisan organizations:process-deletions
```

**Production cron** (on the app server):

```cron
* * * * * cd /path/to/Inventory-Management-SaaS && php artisan schedule:run >> /dev/null 2>&1
```

---

## 6. First API call

### Register a tenant

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

Save `data.token.access_token` and `data.organizations[0].id`.

### Authenticated tenant request

```bash
curl -s http://localhost:8080/api/v1/products \
  -H "Authorization: Bearer <access_token>" \
  -H "X-Organization-Id: <organization_id>" | jq
```

Every tenant-scoped route requires **both** headers.

### Login (existing user)

```bash
curl -s -X POST http://localhost:8080/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"jane@acme.test","password":"password123"}' | jq
```

---

## 7. Demo accounts (local only)

When `APP_ENV=local`, `db:seed` runs `DemoSeeder`:

| Account | Password | Access |
|---------|----------|--------|
| `owner@acme.demo` | `password123` | Acme Warehouse (Org Owner) |
| `owner@beta.demo` | `password123` | Beta Retail (Org Owner) |
| `consultant@demo.test` | `password123` | Both orgs |
| `platform@demo.test` | `password123` | Platform portal at `/platform/login` |

Web login: http://localhost:8000/login (or `:8080` in Docker).

---

## 8. Stripe billing (optional for local dev)

Billing works without real Stripe keys for most CRUD; checkout requires configured test keys.

### 8.1 Environment

```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_STARTER_PRICE_MONTHLY=price_...
STRIPE_STARTER_PRICE_YEARLY=price_...
STRIPE_GROWTH_PRICE_MONTHLY=price_...
STRIPE_GROWTH_PRICE_YEARLY=price_...
STRIPE_BUSINESS_PRICE_MONTHLY=price_...
STRIPE_BUSINESS_PRICE_YEARLY=price_...
```

Create recurring prices in the [Stripe Dashboard](https://dashboard.stripe.com/test/products) for each plan and interval.

### 8.2 Webhook forwarding (test mode)

```bash
stripe listen --forward-to http://localhost:8080/api/stripe/webhook
```

Copy the `whsec_...` secret from the CLI output into `STRIPE_WEBHOOK_SECRET`, then:

```bash
php artisan config:clear
```

Trigger test events:

```bash
stripe trigger checkout.session.completed
stripe trigger invoice.payment_failed
```

Webhook endpoint: `POST /api/stripe/webhook` (signature required; idempotent via `stripe_events` table).

Tenant billing API (authenticated + `X-Organization-Id`):

| Method | Path |
|--------|------|
| GET | `/api/v1/billing` |
| POST | `/api/v1/billing/checkout` |
| POST | `/api/v1/billing/portal` |

---

## 9. Email (optional for local dev)

Default in `.env.example`: `MAIL_MAILER=log` (emails written to `storage/logs/laravel.log`).

Queued mail (welcome, password reset, trial reminder, dunning, low-stock) requires **Horizon running**.

**Production:** set `MAIL_MAILER=ses` and AWS credentials (see Laravel mail docs).

**Test without sending:**

```bash
php artisan test --filter=PrelaunchReadiness
```

Uses `MAIL_MAILER=array` from `phpunit.xml`.

Registration also notifies the platform admin at `ORGANIZATION_REGISTRATION_NOTIFICATION_EMAIL`.

---

## 10. Platform admin setup

```bash
php artisan platform:admin:create platform@example.com "Platform Admin" --password=your-secure-password
```

Or use demo account `platform@demo.test` / `password123` in local.

Portal: `/platform/login` · API: `POST /api/platform/v1/auth/login`

---

## 11. Running tests

```bash
# Full suite (SQLite in-memory)
php artisan test

# Pre-launch features only
php artisan test --filter=PrelaunchReadiness

# Postgres concurrency tests (opt-in, 13 tests)
RUN_STOCK_PG_CONCURRENCY=1 php artisan test
```

CI: `.github/workflows/tests.yml` runs Pest on every push/PR.

---

## 12. Useful Artisan commands

```bash
# Full bootstrap
php artisan app:setup --write-env

# Fix stale Passport client after migrate:fresh
php artisan passport:ensure-password-client --write-env

# Plans only
php artisan db:seed --class=PlanSeeder

# Sync RBAC for existing orgs
php artisan rbac:migrate-organizations

# Legacy orgs missing subscription rows
php artisan platform:subscriptions:backfill

# Clear config after .env changes
php artisan config:clear

# OpenAPI export
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan scramble:export
```

---

## 13. Environment variables reference

See [`.env.example`](../.env.example) for the full list. Key groups:

| Group | Variables |
|-------|-----------|
| App | `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_URL`, `APP_DEBUG` |
| Database | `DB_*` |
| Redis | `REDIS_*`, `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_DRIVER` |
| Passport | `PASSPORT_PASSWORD_CLIENT_ID`, `PASSPORT_PASSWORD_CLIENT_SECRET` |
| Mail | `MAIL_*`, `AWS_*` (for SES) |
| Stripe | `STRIPE_*`, `STRIPE_*_PRICE_*` |
| Subscriptions | `SUBSCRIPTION_*`, `ORGANIZATION_DELETION_GRACE_DAYS` |
| Notifications | `ORGANIZATION_REGISTRATION_NOTIFICATION_EMAIL` |
| API | `API_RATE_LIMIT_PER_MINUTE` |

---

## 14. Production checklist

| Item | Notes |
|------|-------|
| `APP_ENV=production`, `APP_DEBUG=false` | Required |
| `APP_URL` | Must match public URL |
| PostgreSQL + Redis | Managed or self-hosted |
| Horizon | Use `deploy/horizon.conf` with supervisord, or systemd equivalent |
| Scheduler cron | `* * * * * php artisan schedule:run` |
| Mail | SES or transactional provider (`MAIL_MAILER=ses`) |
| Stripe | Live keys + webhook endpoint registered in Dashboard |
| Secrets | AWS Secrets Manager / SSM — not committed `.env` |
| TLS | At load balancer / reverse proxy |
| Health probe | `GET /api/health` or `GET /up` |
| Backups | Automated DB backups with tested restore |
| CI | GitHub Actions on PRs (already in repo) |

Horizon supervisor example: [`deploy/horizon.conf`](../deploy/horizon.conf)

---

## 15. Troubleshooting

### Login returns "These credentials do not match our records" after fresh seed

Passport client ID in `.env` is stale:

```bash
php artisan passport:ensure-password-client --write-env
php artisan config:clear
```

Restart `php artisan serve` / Docker app container.

### 403 on tenant routes

- Missing or wrong `X-Organization-Id` header
- User not a member of that organization
- Organization `status = suspended`

### 402 on writes

- Trial expired, subscription cancelled, or past-due grace expired — see [SUBSCRIPTIONS-AND-PLANS.md](./SUBSCRIPTIONS-AND-PLANS.md)

### Emails not sending locally

- Is Horizon running? (`php artisan horizon` or Docker `horizon` service)
- Check `MAIL_MAILER=log` → read `storage/logs/laravel.log`
- `QUEUE_CONNECTION` must not be `sync` in production-like local testing if you want async behavior; Docker uses `redis`

### Web UI unstyled

```bash
npm install && npm run build
```

### Stripe webhook 400

- Wrong `STRIPE_WEBHOOK_SECRET`
- Missing `Stripe-Signature` header
- Use `stripe listen` secret, not Dashboard secret, when forwarding locally

### Health check 503

```bash
curl -s http://localhost:8080/api/health | jq
```

Inspect which check failed (`database`, `redis`, `queue`). Redis check is skipped when Redis is not used for cache/queue/session.

---

## 16. Project layout (quick map)

```
app/Services/          Business logic
app/Http/Controllers/  API + web entry points
routes/api.php         Tenant API + Stripe webhook + health
routes/platform.php    Platform admin API
routes/web.php         Livewire web UI
routes/console.php     Scheduled commands
database/migrations/   Schema (use migrate:fresh in dev)
tests/Feature/         Pest feature tests
docs/                  Architecture & runbooks
deploy/                Production helpers (Horizon supervisord)
```
