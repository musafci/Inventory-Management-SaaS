# RBAC Permissions Guide

This document explains how **tenant** permissions are defined and generated in Oneapp, and how to add a new permission end-to-end.

> **Platform admin auth is separate.** Super-admin operators use the `platform` Passport guard and `platform_admins` table — not Spatie roles. See [PLATFORM-ADMIN.md](./PLATFORM-ADMIN.md).

For broader RBAC architecture (roles, policies, session auth), see [ARCHITECTURE.md §13](./ARCHITECTURE.md#13-roles-and-permissions-rbac).

---

## How the permission list is generated

Permissions are **defined in code**, **written to the database when an organization is set up**, and **loaded into the UI via the API**. They are not discovered automatically from routes or controllers.

### Flow overview

```
PermissionCatalog.php          ← single source of truth (hardcoded)
        │
        ├──► RolesAndPermissionsSeeder
        │         ├──► permissions table (global catalog)
        │         └──► roles + role_has_permissions (per organization)
        │
        ├──► RoleManagementService::groupedPermissions()
        │         └──► GET /api/v1/roles/permissions → Roles UI checkboxes
        │
        └──► Policies / middleware / OrganizationSession
                  └──► PermissionAuthorizationService → user session permissions[]
```

### 1. Source of truth: `PermissionCatalog.php`

All permission names and module groups live in:

```
app/Permission/PermissionCatalog.php
```

| Method | Purpose |
|--------|---------|
| `groups()` | Module → permission list (Inventory, Orders, Customers, …). Used by the Roles UI. |
| `all()` | Flat list of every permission. Used for validation, caching, and System Owner bypass. |
| `defaultRolePermissions()` | Which permissions each default role receives on seed. |
| `defaultRoleMetadata()` | Role descriptions, `is_protected`, `is_system` flags. |
| `protectedRoleNames()` | Reserved roles (e.g. System Owner). |

Example group structure:

```php
'Inventory' => [
    'inventory.view',
    'inventory.create',
    'inventory.update',
    'inventory.delete',
],
```

Naming convention: `{module}.{action}` (e.g. `orders.sales.refund`, `settings.manage_roles`).

### 2. Database seeding

When a **new tenant registers**, `AuthService` seeds roles and permissions for that organization:

```
AuthService::register()
  → RolesAndPermissionsSeeder::seedRolesForOrganization($organization)
  → user->assignRole('Org Owner')
```

The seeder performs two steps:

**A. Global permission rows** (`permissions` table)

- Reads `PermissionCatalog::all()`
- `firstOrCreate` each permission with guard `api`
- Removes permissions no longer in the catalog

**B. Per-organization roles** (`roles`, `role_has_permissions`)

- Creates default roles: System Owner, Org Owner, Admin, Manager, Warehouse Staff, Sales Staff, Viewer
- Assigns permissions from `defaultRolePermissions()`
- System Owner gets all permissions and is marked `is_protected = true`

**Existing organizations** must be resynced manually:

```bash
php artisan rbac:migrate-organizations
```

### 3. Settings → Roles UI

| What you see | Source |
|--------------|--------|
| Checkbox list in Create/Edit Role modal | `GET /api/v1/roles/permissions` → `PermissionCatalog::groups()` |
| Permission count per role in the table | `GET /api/v1/roles` → Spatie `role_has_permissions` pivot |
| Assigned permissions on save | `PATCH /api/v1/roles/{id}` → `RoleManagementService::syncPermissions()` |

Livewire loads groups on mount:

```php
// app/Http/Livewire/Roles.php
$response = $api->get('/v1/roles/permissions');
$this->permissionGroups = $response['data'] ?? [];
```

### 4. How users receive permissions

Permissions flow through **roles** (Spatie teams scoped by `organization_id`):

```
User → model_has_roles → Role → role_has_permissions → Permission
```

At login / each web request, `PermissionAuthorizationService` resolves effective permissions for the active organization (cached 30 minutes):

- **System Owner** → always `PermissionCatalog::all()`
- **Everyone else** → `$user->getAllPermissions()` for that org

Results are stored in the web session as `permissions[]` and checked via:

- `OrganizationSession::can('permission.name')`
- `@canaccess('permission.name')` Blade directive

### 5. Where permissions are enforced

| Layer | Mechanism |
|-------|-----------|
| API | Resource policies + Spatie `permission` middleware |
| Livewire pages | `EnsuresPermission` trait in `mount()` |
| Blade UI | `@canaccess('permission.name')` |
| System Owner | Unrestricted via `Gate::before` and session helper |

### Key files

| File | Role |
|------|------|
| `app/Permission/PermissionCatalog.php` | Defines all permissions and default role maps |
| `database/seeders/RolesAndPermissionsSeeder.php` | Seeds DB from catalog |
| `app/Services/RoleManagementService.php` | Role CRUD, grouped permissions for API |
| `app/Services/PermissionAuthorizationService.php` | Resolves user permissions (cached) |
| `app/Http/Controllers/Api/V1/RoleController.php` | `GET /roles/permissions` endpoint |
| `app/Support/OrganizationSession.php` | Web session permission checks |

---

## How to create a new permission

Follow every step below. Skipping Step 1 or Step 5 is the most common cause of “permission not showing” or “validation failed” errors.

### Step 1 — Add to `PermissionCatalog.php`

Add the permission under the correct module in `groups()`:

```php
// app/Permission/PermissionCatalog.php

'Inventory' => [
    'inventory.view',
    'inventory.create',
    'inventory.update',
    'inventory.delete',
    'inventory.export',   // new
],
```

### Step 2 — Assign to default roles (recommended)

In `defaultRolePermissions()`, add the permission to roles that should have it by default:

```php
'Manager' => [
    'inventory.view',
    'inventory.create',
    // ...
    'inventory.export',
],
```

Notes:

- **Org Owner**, **Admin**, and **System Owner** use `self::all()` — they receive new permissions automatically.
- **Viewer** and other read-only roles usually should **not** get write/export permissions.

### Step 3 — Enforce on the backend (API)

**Option A — Policy (preferred)**

```php
// app/Policies/ProductPolicy.php

public function export(User $user): bool
{
    return $user->can('inventory.export');
}
```

```php
// In controller
$this->authorize('export', Product::class);
```

**Option B — Route middleware**

```php
// routes/api.php
Route::post('products/export', [ProductController::class, 'export'])
    ->middleware('permission:inventory.export,api');
```

### Step 4 — Enforce on the frontend (web UI)

**Page-level guard** (blocks direct URL access):

```php
// Livewire mount()
$this->ensurePermission('inventory.export');
```

**Button / menu visibility**:

```blade
@canaccess('inventory.export')
    <button wire:click="exportCsv()" class="btn-secondary">Export</button>
@endcanaccess
```

**Sidebar** (when needed):

```blade
@if(\App\Support\OrganizationSession::can('inventory.export'))
    ...
@endif
```

### Step 5 — Sync to the database

Run for **all existing organizations**:

```bash
php artisan rbac:migrate-organizations
```

This will:

1. Insert the new row into `permissions`
2. Re-sync default role assignments from `defaultRolePermissions()`

New tenants created via registration are seeded automatically — no manual step required.

### Step 6 — Assign to custom roles (if needed)

After syncing:

1. **Settings → Roles & Permissions** → Edit role → check the new permission, or
2. API:

```http
PATCH /api/v1/roles/{id}
Content-Type: application/json

{
  "permissions": ["inventory.view", "inventory.export"]
}
```

Users pick up web session changes on the next request (via `WebAuth`). API checks use Spatie immediately; permission cache is cleared when roles are updated.

---

## Worked example: `inventory.export`

### 1. Catalog

```php
// groups()['Inventory']
'inventory.export',

// defaultRolePermissions()['Manager']
'inventory.export',
```

### 2. Policy

```php
public function export(User $user): bool
{
    return $user->can('inventory.export');
}
```

### 3. Controller

```php
public function export(): JsonResponse
{
    $this->authorize('export', Product::class);
    // ...
}
```

### 4. Blade

```blade
@canaccess('inventory.export')
    <button wire:click="exportCsv()" class="btn-secondary">Export CSV</button>
@endcanaccess
```

### 5. Deploy

```bash
php artisan rbac:migrate-organizations
```

---

## Checklist

| # | Task | Location |
|---|------|----------|
| 1 | Define permission | `app/Permission/PermissionCatalog.php` → `groups()` |
| 2 | Default role assignments | Same file → `defaultRolePermissions()` |
| 3 | API enforcement | Policy and/or route middleware |
| 4 | Web enforcement | `EnsuresPermission` + `@canaccess` |
| 5 | DB sync | `php artisan rbac:migrate-organizations` |
| 6 | Custom roles | Settings UI or `PATCH /api/v1/roles/{id}` |

---

## Common pitfalls

| Problem | Cause | Fix |
|---------|-------|-----|
| Permission not in Roles UI | Not in `PermissionCatalog::groups()` | Add to catalog and run migrate command |
| API returns validation error on role save | Permission not in `PermissionCatalog::all()` | Add to `groups()` (which feeds `all()`) |
| User still has old access | Session/cache | Refresh page or re-login; role updates clear permission cache |
| Permission in DB but not enforced | Only seeded, not used in policy/UI | Complete Steps 3–4 |
| Manual DB insert only | Catalog out of sync | Always define in `PermissionCatalog` first |

---

## Related commands

```bash
# Sync permissions and default roles for all organizations
php artisan rbac:migrate-organizations

# Fresh install (dev only)
php artisan migrate --seed
```

---

## Current permission modules

| Module | Permissions |
|--------|-------------|
| Inventory | `inventory.view`, `inventory.create`, `inventory.update`, `inventory.delete` |
| Orders | Purchase: `orders.purchase.*` — Sales: `orders.sales.*` |
| Customers | `customers.view`, `customers.create`, `customers.update`, `customers.delete` |
| Suppliers | `suppliers.view`, `suppliers.create`, `suppliers.update`, `suppliers.delete` |
| Payments | `payments.view` |
| Reports | `reports.view_sales`, `reports.view_inventory`, `reports.view_purchases`, `reports.export` |
| Settings | `settings.view`, `settings.update`, `settings.manage_users`, `settings.manage_roles` |

For the authoritative list, see `app/Permission/PermissionCatalog.php`.
