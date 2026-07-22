# Platform Admin (Super Admin) Guide

Platform operators manage **all tenants** from a layer that sits above organization-scoped RBAC. This is separate from tenant **Org Owner / Admin** roles — different guard, different tables, no `X-Organization-Id` on platform routes.

| Document | Purpose |
|----------|---------|
| **[GETTING-STARTED.md](./GETTING-STARTED.md)** | How to run the project (includes Stripe webhook setup) |
| **This file** | Platform API, portal, impersonation, operator workflows |
| [SUBSCRIPTIONS-AND-PLANS.md](./SUBSCRIPTIONS-AND-PLANS.md) | Plans, subscriptions, limits & enforcement (detailed) |
| [PRICING_PLAN.md](../PRICING_PLAN.md) | Authoritative pricing spec & seed values |
| [PROJECT_BRIEF_FOR_SUPERADMIN.md](../PROJECT_BRIEF_FOR_SUPERADMIN.md) | Product requirements & implementation checklist |
| [ARCHITECTURE.md §14](./ARCHITECTURE.md#14-platform-admin-layer) | Condensed platform overview |
| [SYSTEM-ARCHITECTURE-AND-WORKFLOWS.md §17](./SYSTEM-ARCHITECTURE-AND-WORKFLOWS.md#17-platform-admin-api--portal) | End-to-end platform workflows |
| [RBAC-PERMISSIONS.md](./RBAC-PERMISSIONS.md) | Tenant RBAC only (not platform auth) |
| [PRELAUNCH_READINESS.MD](../PRELAUNCH_READINESS.MD) | Pre-launch checklist & implementation reference |

---

## Architecture overview

```mermaid
flowchart TB
    subgraph PlatformLayer["Platform layer (cross-tenant)"]
        PPortal["/platform/* Livewire portal"]
        PAPI["/api/platform/v1"]
        PGuard["auth:platform guard"]
        PAdmins["platform_admins table"]
    end

    subgraph TenantLayer["Tenant layer (per organization)"]
        TPortal["/login · /dashboard · …"]
        TAPI["/api/v1"]
        TGuard["auth:api + ResolveTenant"]
        Users["users + organization_user"]
    end

    PPortal --> PAPI
    PAPI --> PGuard --> PAdmins
    TPortal --> TAPI
    TAPI --> TGuard --> Users

    PAPI -.->|"manage orgs, plans, flags"| Orgs[(organizations)]
    TAPI --> Orgs
```

**Guard isolation:** Platform tokens must never satisfy tenant middleware, and tenant tokens must never satisfy `auth:platform`. Covered by `tests/Feature/PlatformLayerTest.php`.

---

## Authentication

| Item | Detail |
|------|--------|
| Guard | `platform` (Passport personal access tokens) |
| Model | `App\Models\PlatformAdmin` |
| API login | `POST /api/platform/v1/auth/login` |
| Web login | `/platform/login` → separate session key `platform_auth_token` |
| Public registration | **None** — bootstrap via seeder or Artisan |

### Bootstrap a platform admin

```bash
# Local demo (DemoSeeder)
platform@demo.test / password123

# Manual
php artisan platform:admin:create platform@example.com "Platform Admin" --password=your-secure-password
```

Existing platform admins can create others via `POST /api/platform/v1/platform-admins` or the web UI at `/platform/admins`.

---

## Web portal routes

| URL | Purpose |
|-----|---------|
| `/platform/login` | Sign in |
| `/platform/dashboard` | Tenant counts, recent organizations |
| `/platform/organizations` | Search, filter, paginate all tenants |
| `/platform/organizations/{id}` | Status, subscription, feature flags, support notes, activity audit, impersonation |
| `/platform/activity-logs` | Cross-tenant activity analysis |
| `/platform/admins` | Create / remove platform admin accounts |

Portal UI matches tenant styling (sidebar, cards, toasts, confirm dialogs). Internal API calls use `PlatformApiClient` with the platform session token.

---

## Platform API reference

Base path: `/api/platform/v1` · Middleware: `auth:platform`

### Auth

| Method | Path | Purpose |
|--------|------|---------|
| POST | `/auth/login` | Issue platform access token |
| POST | `/auth/logout` | Revoke current token |
| GET | `/auth/me` | Current platform admin profile |

### Plans & subscriptions

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/plans` | List active plans and limit JSON |
| GET | `/organizations/{id}/subscription` | Current subscription for tenant |
| PATCH | `/organizations/{id}/subscription` | Assign plan, set subscription status |

### Activity audit

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/organizations/{id}/activity-logs` | Paginated audit trail for tenant (filter by event, resource type, dates) |
| GET | `/activity-logs` | Cross-tenant activity feed with filters |
| GET | `/activity-logs/summary` | Aggregate counts by event, resource type, and top organizations |

Activity logs store `organization_id` on every tenant audit entry (sales orders, purchase orders, payments, stock movements, roles). Portal UI: `/platform/activity-logs` and per-org section on organization detail.

**Plans (seeded):** `starter`, `growth`, `business`, `enterprise` — see [PRICING_PLAN.md](../PRICING_PLAN.md) for exact values. There is no separate "trial" plan row; new orgs trial on **Growth** for 14 days.

| Plan | Monthly | Annual | Warehouses | Users | Products | Orders/mo | API/min |
|------|---------|--------|------------|-------|----------|-----------|---------|
| starter | $29 | $288 | 1 | 3 | 200 | 300 | — |
| growth | $79 | $780 | 3 | 10 | 2,000 | 2,000 | 60 |
| business | $199 | $1,980 | 10 | 25 | ∞ | 10,000 | 300 |
| enterprise | custom | custom | ∞ | ∞ | ∞ | ∞ | ∞ |

**Limit keys** in `plans.limits` JSON:

| Key | Enforced on |
|-----|-------------|
| `max_warehouses` | `POST /warehouses` |
| `max_users` | `POST /users` (invite member) |
| `max_products` | `POST /products` |
| `max_orders_per_month` | `POST /purchase-orders`, `POST /sales-orders` (combined count) |
| `api_rate_limit_per_minute` | Tenant API throttle per org+user (`null` = no API access) |

All self-serve plans use `grace_buffer_percent = 10`. Enterprise has `is_custom = true` (contact sales, not in self-serve checkout).

`organizations.plan` is a **denormalized cache** synced from `organization_subscriptions`.

### Organizations

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/organizations` | Paginated tenant directory |
| GET | `/organizations/{id}` | Detail + subscription + members |
| PATCH | `/organizations/{id}` | Update `status`, legacy `plan` string |

**Organization status:** `trial`, `active`, `suspended`

### Support notes (internal)

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/organizations/{id}/support-notes` | List internal operator notes |
| POST | `/organizations/{id}/support-notes` | Add note (`note` required) |

Never exposed on tenant-facing `/api/v1`.

### Feature flags

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/organizations/{id}/feature-flags` | Global flags + per-org overrides |
| PATCH | `/organizations/{id}/feature-flags/{flagId}` | Set `{ "enabled": true/false }` |

**Seeded flags:** `advanced_reporting`, `multi_warehouse_transfers`, `api_integrations`

### Impersonation

| Method | Path | Purpose |
|--------|------|---------|
| POST | `/organizations/{id}/impersonate` | `{ user_id, reason }` — issue short-lived tenant token |
| POST | `/impersonation/end` | End active impersonation session |

- `reason` is required (min 10 chars)
- Logged append-only in `impersonation_logs`
- Tenant `GET /api/v1/auth/me` includes `impersonation` object when active
- **React Native / mobile:** show visible impersonation indicator (cross-team coordination)

### Platform admin management

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/platform-admins` | List operators |
| POST | `/platform-admins` | Create `{ name, email, password }` |
| DELETE | `/platform-admins/{id}` | Remove (cannot delete last admin) |

---

## Database schema

| Table | Purpose |
|-------|---------|
| `platform_admins` | Super-admin identities |
| `plans` | Plan slug, monthly/annual pricing, limits JSON, grace buffer |
| `organization_subscriptions` | Source of truth: org ↔ plan, status, trial/period dates, Stripe refs |
| `feature_flags` | Global flag definitions |
| `organization_feature_flags` | Per-org enabled/disabled override |
| `support_notes` | Internal operator notes |
| `impersonation_logs` | Audit trail: admin, org, user, reason, token, start/end |

Registration automatically creates a **14-day Growth trial** via `OrganizationSubscriptionService::assignTrialPlan()` — no separate trial plan row.

---

## Tenant enforcement

Platform actions affect tenant access through middleware and services — not UI-only checks.

### Suspension

When `organizations.status = suspended`:

| Layer | Behavior |
|-------|----------|
| API | `ResolveTenant` returns **403** — *"This organization has been suspended."* |
| Web | `WebAuth` clears session and redirects to `/login` with error |

### Subscription status & trial expiry

| Condition | Behavior |
|-----------|----------|
| `subscription.status = cancelled` + write | **402** — subscription cancelled |
| `subscription.status = cancelled` + read | **OK** — read-only access |
| `subscription.status = past_due` within grace + write | **OK** — grace period (`SUBSCRIPTION_PAST_DUE_GRACE_DAYS`) |
| `subscription.status = past_due` past grace + write | **402** — update billing |
| `subscription.status = past_due` + read | **OK** |
| `subscription.status = expired` + write | **402** — trial ended; choose a plan |
| `subscription.status = expired` + read | **OK** — read-only access to existing data |
| Trial `trial_ends_at` in the past | Auto-marked `expired` (daily job or next request) |
| Missing subscription row | **403** — run `platform:subscriptions:backfill` for legacy orgs |
| Stripe `invoice.payment_failed` | Marks `past_due`, sends dunning email |
| Stripe `invoice.paid` after past due | Restores `active`, clears `past_due_at` |

### Plan limits (graduated)

`PlanLimitService` uses a **graduated response** per resource:

| Usage vs. limit | Behavior |
|-----------------|----------|
| Under 90% | Normal |
| 90%–100% | Normal + `meta.plan_warning: "approaching_limit"` |
| 100%–110% (grace buffer) | Allowed + `meta.plan_warning: "over_limit_grace"` |
| Beyond grace buffer | **422** — "Upgrade required" |

Checks are per-resource — being over on products does not block order creation.

**Key files:** `app/Services/PlanLimitService.php`, `app/Services/OrganizationSubscriptionService.php`, `app/Http/Middleware/ResolveTenant.php`

---

## Key files

| Area | Path |
|------|------|
| Platform API routes | `routes/platform.php` |
| Web portal routes | `routes/web.php` (`/platform/*`) |
| API controllers | `app/Http/Controllers/Api/Platform/V1/*` |
| Livewire pages | `app/Http/Livewire/Platform/*` |
| Session bridge | `app/Services/Web/PlatformApiClient.php`, `PlatformSessionService.php` |
| Web auth | `app/Http/Controllers/Web/PlatformAuthController.php` |
| Middleware | `app/Http/Middleware/PlatformWebAuth.php` |
| Subscription | `app/Services/OrganizationSubscriptionService.php` |
| Plan limits | `app/Services/PlanLimitService.php` |
| Impersonation | `app/Services/ImpersonationService.php` |
| Support notes | `app/Services/SupportNoteService.php` |
| Feature flags | `app/Services/FeatureFlagService.php` |
| Platform admins | `app/Services/PlatformAdminService.php` |
| Bootstrap command | `app/Console/Commands/CreatePlatformAdminCommand.php` |
| Plan seeder | `database/seeders/PlanSeeder.php` |
| Tests | `tests/Feature/PlatformLayerTest.php`, `PlatformAdminTest.php`, `Web/PlatformPortalTest.php` |

---

## Operations

```bash
# Migrations (includes platform tables)
php artisan migrate

# Seed plans + feature flags
php artisan db:seed --class=PlanSeeder

# Create platform admin
php artisan platform:admin:create platform@demo.test "Platform Admin" --password=password123

# Legacy orgs created before subscriptions existed
php artisan platform:subscriptions:backfill

# Expire past-due trials (also runs daily via scheduler)
php artisan subscriptions:expire-trials

# Trial-ending reminder emails (also scheduled daily)
php artisan subscriptions:notify-trial-ending

# Hard-delete orgs past deletion grace (also scheduled daily)
php artisan organizations:process-deletions

# Full bootstrap (includes PlanSeeder via DatabaseSeeder)
php artisan app:setup --write-env
```

### Demo credentials (local)

| Account | Password | Access |
|---------|----------|--------|
| `platform@demo.test` | `password123` | `/platform/login` + `/api/platform/v1` |

---

## Out of scope (documented deferrals)

| Item | Status |
|------|--------|
| Stripe self-serve checkout | Implemented — Starter, Growth, Business via Settings → Billing |
| Enterprise checkout | Contact sales only (`is_custom = true`) |
| Plan-based API rate limiting | Implemented via `api_rate_limit_per_minute` per plan |
| Auth rate limiting (login/register/forgot) | Implemented — IP + email limiters |
| Stripe webhook idempotency | Implemented — `stripe_events` table |
| Tenant GDPR export / deletion | Implemented — owner-only API; see [PRELAUNCH_READINESS.MD](../PRELAUNCH_READINESS.MD) |
| Tenant-visible support notes | Internal only by design |
