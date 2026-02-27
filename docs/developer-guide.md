# SpareHub Developer Guide

## 1. System Overview

SpareHub is a Laravel 12 multi-tenant ERP/workshop app built with Stancl Tenancy.

- Central app handles: system CRM, tenant creation, plans, tenant-user provisioning, and login identity mapping.
- Tenant app handles: per-tenant operations (master data, workshop, sales, purchases, employees, reports, settings).
- Tenancy strategy is path-based: all tenant routes run under `/firm/{tenant}`.

Key route files:
- `routes/web.php` (central + system routes)
- `routes/tenant.php` (tenant-scoped routes)

## 2. Multi-Tenancy Architecture

### 2.1 Context split

Central DB contains global entities:
- `tenants`
- `plans`
- `system_users`
- `login_maps`
- `login_attempts`
- `domains` (present by package, not required for current path-based flow)

Each tenant has its own DB with business data:
- branches/users/customers/vendors/products/stock/sales/purchases/workshop/employees/etc.

### 2.2 Tenant bootstrap pipeline

When a tenant is created, `TenancyServiceProvider` wires `TenantCreated` to a job pipeline:
1. `CreateDatabase`
2. `MigrateDatabase`
3. `SeedDefaultBranch`

See `app/Providers/TenancyServiceProvider.php`.

## 3. Authentication & Login Flow

### 3.1 Central credential lookup + tenant handoff

Tenant login (`/`) does not directly authenticate against tenant DB first.

Flow:
1. `App\Actions\Auth\Tenant\LoginAction` validates email/password in central `login_maps`.
2. A short-lived nonce is written to central cache.
3. User is redirected to signed `tenant.authenticate` URL under `/firm/{tenant}/authenticate`.
4. Tenant context consumes nonce, loads tenant `users` row, then logs in via `auth:user` guard.

Files:
- `app/Actions/Auth/Tenant/LoginAction.php`
- `app/Http/Controllers/Auth/Tenant/AuthController.php`
- `app/Models/LoginMap.php`

### 3.2 Why `login_maps` exists

`login_maps` is the central identity bridge:
- maps tenant user credentials to a tenant ID + tenant user UUID
- enables central credential check before tenant context switch
- stores status and login audit fields (`last_login_at`, `last_login_ip`)

System-side tenant owner/user creation updates this table.

Files:
- `app/Http/Controllers/System/TenantController.php`
- `app/Http/Controllers/System/TenantUserController.php`

### 3.3 Tenant two-factor authentication (implemented)

#### Source of truth (table/column ownership)

2FA is intentionally split between tenant policy and user enrollment state.

1. Policy scope (tenant branch-level):
- Table: `tenant_settings` (tenant DB)
- Columns:
  - `two_factor_enabled`
  - `two_factor_method` (`email` | `authenticator`)

2. Enrollment scope (tenant user-level):
- Table: `users` (tenant DB)
- Columns:
  - `two_factor_type` (currently `app` for authenticator)
  - `two_factor_secret` (TOTP secret)
  - `two_factor_verified_at` (enrollment verification timestamp)
  - `two_factor_recovery_codes` (hashed backup codes)

3. Challenge/session scope (temporary only):
- Session keys:
  - `two_step.required`
  - `two_step.verified`
  - `two_step.method`
  - `two_step.code`
  - `two_step.expires_at`
  - `two_step.setup_required`
  - `two_step.enrollment_required`

4. Login telemetry/audit:
- Table: `login_attempts` (central DB)
- Used for login attempt tracking and dashboard health metrics, not for 2FA policy.

Important distinction:
- Tenant 2FA uses tenant `users` + tenant `tenant_settings`.
- Central `system_users` table is for system panel auth and is not tenant-user 2FA state.

#### Runtime flow

1. User is authenticated through signed tenant handoff (`tenant.authenticate`).
2. Branch policy is loaded from tenant `tenant_settings`.
3. If 2FA disabled: user enters dashboard directly.
4. If method is `email`: code is sent and user verifies on `/firm/{tenant}/two-step`.
5. If method is `authenticator` and user is not enrolled/verified:
   - user is redirected to `Profile > Security` enrollment page.
6. If method is `authenticator` and user is enrolled/verified:
   - user verifies on `/firm/{tenant}/two-step` using app code or backup code.

#### Enforcement guard flow (`tenant.two-step` middleware)

`EnsureTwoStepVerified` controls protected tenant routes when 2FA challenge is pending.

Decision behavior:
1. If `two_step.required=false` or `two_step.verified=true`: request continues.
2. If enrollment is pending (`two_step.enrollment_required=true`):
   - allowed routes:
     - `tenant.profile.security.*` (complete authenticator setup)
     - `tenant.settings.*` (disable 2FA if enabled by mistake)
     - `tenant.logout`
   - all other protected routes redirect to `tenant.profile.security.show` with warning.
3. If enrollment is not pending but verification is required:
   - allowed routes:
     - `tenant.two-step`, `tenant.two-step.verify`, `tenant.logout`
   - all other protected routes redirect to `tenant.two-step` with warning.

Stale-session self-heal:
- Middleware checks if session says `enrollment_required=true` but user already has both:
  - `users.two_factor_secret`
  - `users.two_factor_verified_at`
- If yes, it clears stale `two_step.*` enrollment/challenge session keys and allows navigation.

Settings-side unlock behavior:
- When tenant 2FA is disabled from Settings (`two_factor_enabled=false`), `SettingController@update` clears all `two_step.*` challenge/enrollment session keys immediately so user is not stuck in security redirects.

UX behavior:
- Enrollment QR/manual key is shown only on Profile Security enrollment/reset flow.
- `/two-step` remains verification-only.

Files:
- `app/Http/Controllers/Auth/Tenant/AuthController.php`
- `app/Actions/Auth/Tenant/IssueTwoStepCodeAction.php`
- `app/Actions/Auth/Tenant/VerifyTwoStepCodeAction.php`
- `app/Http/Controllers/Tenant/ProfileController.php`
- `resources/views/tenants/profile/security.blade.php`
- `resources/views/auth/tenant/two-step-verification.blade.php`

## 4. Branch Scoping Logic

Branch scoping is session-driven and applied globally through `BranchScopedBySession` trait.

Behavior:
- On tenant routes, models using trait auto-filter by `session('tenant.current_branch_id')`.
- On save, if model has fillable `branch_id` and it is empty, branch is auto-injected.
- Scope is disabled for console context.
- Middleware also applies tenant timezone from `tenant_settings.timezone` using current branch session.

Files:
- `app/Models/Concerns/BranchScopedBySession.php`
- `app/Http/Middleware/EnsureBranchSelected.php`
- `app/Http/Controllers/Tenant/BranchSwitchController.php`

## 5. Route/Module Structure

### 5.1 System module (`/system/*`)

Primary resources:
- `plans`
- `tenants`
- `tenant-users`

Central sidebar is in:
- `resources/views/layouts/system/shared/sidebar.blade.php`

### 5.2 Tenant module (`/firm/{tenant}/*`)

High-level groups from `routes/tenant.php` and tenant sidebar:
- Dashboard + End of Day
- Employee (attendance, salaries)
- Expenses
- Master Data
- Workshop
- Sales
- Purchases
- Access Control (users, roles, permissions)
- Reports
- Settings/Profile

Tenant sidebar:
- `resources/views/layouts/shared/sidebar.blade.php`

### 5.3 Recent Activity module (`/firm/{tenant}/activity-timeline`)

Recent Activity is available under:
- Sidebar: `System -> Recent Activity`
- Route: `tenant.activity-timeline.index`
- Controller: `app/Http/Controllers/Tenant/ActivityTimelineController.php`
- View: `resources/views/tenants/activity-timeline/index.blade.php`

Data source:
- Reads tenant audit events from tenant DB table `tenant_activity_timelines`
- Each tenant only reads its own DB, so no cross-tenant filter is required.

Covered event/module types in timeline:
- Authentication:
  - login success (`auth_login_succeeded`)
  - logout (`auth_logout`)
  - failed login attempts (`auth_failure`)
- Sales / POS:
  - POS sale create (`pos_sale_created`)
  - sale create/update/delete (`sale_created`, `sale_updated`, `sale_deleted`)
  - sale payment record/update/delete (`sale_payment_recorded`, `sale_payment_updated`, `sale_payment_deleted`)
- Purchases:
  - purchase create/update/delete (`purchase_created`, `purchase_updated`, `purchase_deleted`)
  - vendor payment record/update/delete (`vendor_payment_recorded`, `vendor_payment_updated`, `vendor_payment_deleted`)
  - purchase return create/update/delete (`purchase_return_created`, `purchase_return_updated`, `purchase_return_deleted`)
- Workshop:
  - job card create/update/delete (`job_card_created`, `job_card_updated`, `job_card_deleted`)
- Expenses:
  - expense create/update/delete (`expense_created`, `expense_updated`, `expense_deleted`)
- Inventory:
  - stock adjustment (`inventory_stock_adjusted`)
- Branch context:
  - branch switch (`branch_switched`)
  - branch create/update/delete (`branch_created`, `branch_updated`, `branch_deleted`)
- Tenant settings:
  - settings updates (`settings_updated`)
  - 2FA policy changes (`two_factor_policy_changed`)
- Profile Security / 2FA:
  - email OTP issued (`two_step_code_issued`)
  - challenge required (`two_step_challenge_required`)
  - enrollment required (`two_factor_enrollment_required`)
  - verification success/failure (`two_step_verified`, `two_step_verification_failed`)
  - enrollment started (`two_factor_enrollment_started`)
  - enrollment verified (`two_factor_enrollment_verified`)
  - authenticator secret reset (`two_factor_secret_reset`)
  - backup codes regenerated (`two_factor_backup_codes_regenerated`)
- User/Profile management:
  - profile update (`profile_updated`)
  - user create/update/delete (`user_created`, `user_updated`, `user_deleted`)

Current scope note:
- Timeline currently focuses on security + context-critical events.
- Sales/Purchases/Inventory operational events can be added later using the same tenant timeline pattern.

## 6. Data Model: Core Dependencies

Below is dependency-oriented mapping (parent -> child).

### 6.1 Tenant core

- `branches` -> `users`, `customers`, `vendors`, `tenant_settings`, transactional tables
- `users.branch_id` nullable FK to `branches`

Seeders ensure a default branch exists with baseline master data.

### 6.2 Master data

- `categories.parent_id` self-reference
- `products` references:
  - `categories`
  - `brands`
  - `taxes` (default tax)
  - `units` (default unit)
- `product_prices` references `products` and optional `branches`
- `service_catalog` references `branches` and optional default `taxes`

Practical example:
- Product `Engine Oil 1L`:
  - `default_unit_id` -> `Bottle`
  - `default_tax_id` -> `VAT 18%`
  - `brand_id` -> `Castrol`
  - `category_id` -> `Lubricants`
  - `track_stock` -> `true`

### 6.3 CRM entities

- `customers` belongs to `branches`
- `customer_vehicles` belongs to `customers`
- `vendors` belongs to `branches`

### 6.4 Workshop

- `job_cards` belongs to:
  - `branches`
  - `customers`
  - optional `customer_vehicles`
  - optional assigned `users`
- `job_card_services` belongs to `job_cards`, optional `service_catalog`, optional technician `users`
- `job_card_parts` belongs to `job_cards` and `products`

Sidebar purpose:
- `Job Cards`: parent workshop ticket and lifecycle state.
- Service and part lines are managed inline in Job Card create/edit.

### 6.5 Sales

- `sales` belongs to `branches`, optional `customers`, optional `job_cards`, optional creator `users`
- `sale_items` belongs to `sales`, `branches`, optional `products`, optional `service_catalog`, optional `job_card_services`
- `sale_payments` belongs to `sales`, `branches`, optional receiver `users`
- `sale_holds` belongs to `branches`, optional `customers`, optional creator `users`

### 6.6 Purchases

- `purchases` belongs to `branches`, required `vendors`, optional creator `users`
- `purchase_items` belongs to `purchases`, `branches`, `products`, optional `taxes`
- `purchase_returns` belongs to `branches`, `vendors`, optional `purchases`, optional creator `users`
- `purchase_return_items` belongs to `purchase_returns`, optional `purchase_items`, required `products`, optional `taxes`
- `vendor_payments` belongs to `branches`, `vendors`, optional `purchases`, optional creator `users`

Practical flow example:
1. Create purchase `PI-1004` for vendor `City Auto Supplier`.
2. Add `purchase_items` for products and tax amounts.
3. `syncStockForPurchaseItems()` increments `inventory_stocks` for tracked products.
4. `vendor_payments` entries reduce outstanding payable.
5. Purchase return reverses stock and payable as needed.

### 6.7 Inventory

- `inventory_stocks` belongs to `products`, `branches`
- Unique key per (`product_id`, `branch_id`)
- `stock_moves` belongs to `products`, `branches`, optional creator `users`

#### Stock Movement Flow (`track_stock = true`)

This flow is complete for tracked products:
1. Product is created with opening stock / stock row.
2. Sale (`POS`, `Sales`, `Sale Items`) decrements stock.
3. Purchase (`Purchases`, `Purchase Items`) increments stock.
4. Purchase Return (`Purchase Returns`, `Purchase Return Items`) decrements stock back.

Notes:
- Stock is adjusted only when `products.track_stock = true`.
- Product listing shows current stock against opening stock (for example `17/20`).
- Opening stock is managed from product create/edit.
- Stock movement trail is reviewed from `Products > History`.

### 6.11 Units and Fractional Quantities

`units` table has:
- `code`
- `name`
- `is_fractional`
- `status`

Current usage:
- `products.default_unit_id` references units.
- Units are loaded in product create/edit/show flows.

Current limitation:
- `is_fractional` is not yet used to enforce quantity rules in sales/purchase validation.
- Quantity fields in `SaleRequest` and `PurchaseRequest` are generic numeric rules.

Practical examples:
- `Piece` (`is_fractional=false`): Spark plug, brake pad set.
- `Bottle` (`is_fractional=false`): Engine Oil 1L bottle.
- `Liter` (`is_fractional=true`): Bulk coolant.
- `Kg` (`is_fractional=true`): Grease by weight.

Suggested future enforcement:
- If unit is non-fractional, quantity must be integer.
- If unit is fractional, decimal quantity is allowed.
- Apply consistently in sales, purchases, returns, and stock adjustments.

### 6.8 Expense module

- `expenses` belongs to `branches`, optional creator `users`
- Stores daily operational spend (title/category/amount/method/date/reference/notes)
- Used by End Of Day to compute:
  - `expenses_count`
  - `expenses_total`
  - `cash_out` (vendor payments + expenses)

### 6.9 Employee module

- `employee_attendances` belongs to `branches` and `users`
- Unique per (`branch_id`, `user_id`, `attendance_date`)
- `employee_salaries` belongs to `branches` and `users`
- Unique per (`branch_id`, `user_id`, `salary_month`)

### 6.10 Access control

Per-tenant Spatie permission tables:
- `permissions`
- `roles`
- `model_has_permissions`
- `model_has_roles`
- `role_has_permissions`

Seeder `TenantRolePermissionSeeder` creates baseline permissions and roles:
- `tenant_owner`
- `admin`
- `manager`
- `cashier`

`TenantDemoSeeder` assigns `tenant_owner` to demo owner.

## 7. Seeder Strategy

### 7.1 Central seed flow

`DatabaseSeeder` calls central demo seeding and tenant demo seeding logic.

### 7.2 Tenant bootstrap seeder

`TenantBootstrapSeeder` creates mandatory baseline for a new tenant DB:
- default branch
- base taxes/units/categories/brands
- starter products/prices
- service catalog
- sample customer
- tenant settings

## 8. End Of Day Logic

`EndOfDayController` aggregates branch/day stats:
- sales totals/count
- purchase totals/count
- cash in from sale payments
- cash out from vendor payments + expenses
- open job cards
- attendance in/out/missing checkout
- monthly payroll paid/unpaid totals

File:
- `app/Http/Controllers/Tenant/EndOfDayController.php`

Practical scenario:
- Day totals: sales `120,000`, purchases `35,000`, expenses `5,000`, vendor payments `20,000`.
- Cash-in from sale payments `85,000`.
- Cash-out = `25,000`.
- Net cash movement = `+60,000`.
- Used by manager for same-day working capital decisions.

## 9. Practical Dev Rules for This Codebase

- Use `tenant()->run(...)` when creating/updating tenant DB data from central context.
- Keep `login_maps` synced whenever tenant user credentials are created or changed centrally.
- For tenant business models, honor branch scoping conventions.
- Prefer existing CRUD patterns (controller + form request + blade partial modal where used).
- Keep route names under existing groups (`tenant.*` / `system.*`) to preserve middleware and sidebar active states.

## 10. Current Known Design Decisions

- Path-based tenancy is active; domain table is not required for day-to-day flow.
- Tenant users can be provisioned from system side (`system/tenant-users`) so multiple users share one tenant but keep separate credentials.
- Employee module currently uses attendance and salary tables tied to tenant users per branch.

## 11. Suggested Onboarding Checklist for New Developers

1. Read `routes/web.php` and `routes/tenant.php` for app surface.
2. Read `TenancyServiceProvider` for lifecycle hooks.
3. Read `TenantController` + `TenantUserController` + `Auth\Tenant\AuthController` for identity flow.
4. Read `BranchScopedBySession` + `EnsureBranchSelected` before touching tenant queries.
5. Run migrations/seeders and inspect one tenant DB to validate FK chain.

## 12. Module-Wise Practical Scenarios

- Dashboard:
  Verify KPI cards and trend chart data against seeded tenant transactions.
- Master Data:
  Create category/brand/unit/tax, then create product and confirm default links resolve in listing.
- Sales:
  Create sale with 2 lines (product + service), post payment, assert stock decrement for tracked product.
- Purchases:
  Create purchase with vendor and assert stock increment + payable creation.
- Workshop:
  Create job card, attach service and part lines, then verify billing linkage to sale.
- Inventory:
  Update opening stock from product edit and verify `stock_moves` entry + updated `inventory_stocks`.
- Employees:
  Mark attendance and create salary record for same user/month branch context.
- Reports:
  Open per-report page (sales/purchases/etc.) and export page-specific PDF route.

## 13. Action Pattern Used in Tenant Modules

Tenant modules follow an action-first mutation pattern for consistency and testability.

Read pattern:
- Controller handles request/auth/response concerns.
- Validation stays in `FormRequest`.
- Mutations are delegated to `App\Actions\Tenant\...`.

Write pattern:
1. Controller receives validated payload.
2. Controller calls one action (or a small action chain).
3. Action performs DB mutation and related side effects (audit log, cache/version bump, policy/session updates).
4. Controller returns redirect/view/json.

Benefits in this codebase:
- Side effects are centralized and reusable.
- Controllers stay thin and predictable.
- Mutation behavior is easier to test without full HTTP flow.

Examples:
- Branch mutations:
  - `app/Actions/Tenant/Branch/CreateBranchAction.php`
  - `app/Actions/Tenant/Branch/UpdateBranchAction.php`
  - `app/Actions/Tenant/Branch/DeleteBranchAction.php`
- Tenant settings mutation:
  - `app/Actions/Tenant/Setting/UpsertTenantSettingAction.php`

Rule of thumb:
- If a change mutates tenant business state, implement/extend an action instead of adding mutation logic in controller methods.

### 13.1 Shared Action Template (Audit + Cache Side Effects)

Use this snippet when creating new tenant mutation actions:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Tenant\{Module};

use App\Support\AuditTimelineLogger;
use App\Support\HeaderContextCache;
use Illuminate\Support\Facades\Auth;

final class {ActionName}
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): mixed
    {
        // 1) Perform primary mutation first.
        $result = /* create/update/delete */;

        // 2) Bump header cache version only if this mutation affects header context
        //    (branches, selected-branch settings, displayed role/context).
        HeaderContextCache::bumpForCurrentTenant();

        // 3) Record audit event with stable event key + useful properties.
        AuditTimelineLogger::log(
            event: '{module}_{action}',
            description: '{Human readable summary}.',
            causer: Auth::guard('user')->user(),
            subject: $result,
            properties: [
                // 'changed_attributes' => [...],
                // 'branch_id' => ...,
            ],
        );

        return $result;
    }
}
```

Checklist before merging a new action:
- Keep validation in a `FormRequest` and pass only validated payload into action.
- Keep controller thin: call action and return response only.
- Add/verify `AuditTimelineLogger::log(...)` with meaningful event/properties.
- Call `HeaderContextCache::bumpForCurrentTenant()` only when header data can become stale.
- Add/update focused Pest tests for the mutation behavior and required side effects.

## 14. Header Cache Invalidation Strategy

Tenant header data is cached for 2 minutes in the header view composer:
- `app/Providers/AppServiceProvider.php` (`View::composer('layouts.shared.header', ...)`)

To avoid stale header data after branch/settings/role changes, the cache key is versioned.

Current cache key parts:
- tenant id
- user id
- current branch id (session)
- tenant header cache version
- current user role fingerprint (`getRoleNames()`)

Implementation:
- Version helper:
  - `app/Support/HeaderContextCache.php`
- Composer reads version with:
  - `HeaderContextCache::currentVersion($tenantId)`
- Mutating actions bump version with:
  - `HeaderContextCache::bumpForCurrentTenant()`

Where version bump is currently applied:
- branch create/update/delete actions
- tenant setting upsert action

Operational rule:
- Any new mutation that affects header payload (`branches`, selected-branch settings, displayed role/context) must call `HeaderContextCache::bumpForCurrentTenant()`.

## 15. Global AJAX Table Search & Sorting

SpareHub includes a reusable table-search utility in:
- `resources/js/app.js`

It initializes automatically on DOM ready for any container with `data-ajax-table-search`.

### 15.1 What it handles

- Debounced keyword search (default `350ms`)
- In-flight request cancellation (AbortController)
- Partial HTML refresh of:
  - table head
  - table body
  - pagination container
- URL query sync via `history.replaceState`
- Search spinner lifecycle with minimum visible duration
- Sort link interception (`data-ajax-sort-link`)
- Sort cycle support: unsorted -> asc -> desc -> unsorted

### 15.2 Required markup contract

Add a root container and configure selectors using data attributes:

```html
<div
  data-ajax-table-search
  data-form-selector="#brands-search-form"
  data-input-selector="#search"
  data-table-head-selector="#brands-table thead"
  data-table-body-selector="#brands-table tbody"
  data-pagination-selector="[data-brands-pagination]"
  data-loading-selector="#brands-search-loading"
  data-sort-link-selector="[data-ajax-sort-link]"
  data-search-param="search"
  data-debounce="350"
  data-min-loading-visible="220"
>
  ...
</div>
```

Inside this container, ensure:
- Search form + input exist and submit with `GET`
- Table has stable `<thead>` selector
- Table has stable `<tbody>` selector
- Pagination wrapper has stable selector
- Loading element toggles with `opacity-0 pe-none` classes
- Sortable header links use `data-ajax-sort-link`

### 15.3 Notes for new modules

- Keep server filtering in controller (`request('search')`) so it works for full-page and AJAX refresh.
- Do not duplicate fetch/debounce logic inline in each Blade view; prefer this global utility.
- Keep row action handlers delegated (`document.addEventListener('click', ...)`) if rows are replaced dynamically.
- Keep sorting on server (`sort_by`, `sort_direction`) and whitelist columns in controller.
- For better UX, implement 3-state sort reset so third click removes sorting and returns default order.

### 15.4 Search Skeleton (Copy/Paste)

Use this starter when converting a table page:

```php
// Controller@index skeleton
$perPage = min(max($request->integer('per_page', 15), 5), 100);
$search = mb_trim($request->string('search')->toString());

$items = Model::query()
    ->when($search !== '', function (Builder $query) use ($search): void {
        $query->where(function (Builder $builder) use ($search): void {
            $builder
                ->where('code', 'like', sprintf('%%%s%%', $search))
                ->orWhere('name', 'like', sprintf('%%%s%%', $search));
        });
    })
    ->latest()
    ->paginate($perPage)
    ->withQueryString();
```

```blade
{{-- Blade skeleton --}}
<div class="card custom-card border-0 shadow-sm h-100"
    data-ajax-table-search
    data-form-selector="#module-search-form"
    data-input-selector="#module-search"
    data-table-head-selector="#module-table thead"
    data-table-body-selector="#module-table tbody"
    data-pagination-selector="[data-module-pagination]"
    data-loading-selector="#module-search-loading"
    data-sort-link-selector="[data-ajax-sort-link]"
    data-search-param="search"
    data-debounce="350"
    data-min-loading-visible="220">

    <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
        <div class="card-title mb-0">{{ __('Module Name') }}</div>

        <form method="GET" action="{{ route('tenant.module.index') }}"
            class="d-flex align-items-end gap-2 flex-wrap"
            id="module-search-form">
            <div class="position-relative">
                <input type="text"
                    name="search"
                    id="module-search"
                    class="form-control pe-5"
                    value="{{ request('search') }}"
                    placeholder="{{ __('Search...') }}">

                <span id="module-search-loading"
                    class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                    style="transition: opacity 0.2s ease;"
                    aria-hidden="true">
                    <span class="spinner-border spinner-border-sm"></span>
                </span>
            </div>
        </form>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0" id="module-table">
                <thead>
                    <tr>
                        <x-sortable-column :label="__('Name')" column="name" :current-sort-by="$sortBy"
                            :current-sort-direction="$sortDirection" />
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>...</tbody>
            </table>
        </div>

        <div class="mt-3" data-module-pagination>
            {{ $items->links() }}
        </div>
    </div>
</div>
```

```php
// Sorting skeleton (Controller@index)
$sortBy = $request->string('sort_by')->toString();
$sortDirection = $request->string('sort_direction')->toString();
$allowedSortColumns = ['created_at', 'name'];
$activeSortBy = in_array($sortBy, $allowedSortColumns, true) ? $sortBy : null;
$activeSortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'asc';

$itemsQuery = Model::query();

if ($activeSortBy !== null) {
    $itemsQuery->orderBy($activeSortBy, $activeSortDirection);
} else {
    $itemsQuery->latest();
}

$items = $itemsQuery
    ->paginate($perPage)
    ->withQueryString();

return view('tenants.module.index', [
    'items' => $items,
    'sortBy' => $activeSortBy,
    'sortDirection' => $activeSortDirection,
]);
```

```blade
{{-- Reusable sortable header component --}}
{{-- resources/views/components/sortable-column.blade.php --}}
{{-- 3-state click: unsorted -> asc -> desc -> unsorted --}}
```

Testing checklist for converted pages:
- Index test should assert:
  - `data-ajax-table-search`
  - `data-ajax-sort-link`
  - search form id (example `module-search-form`)
  - loading spinner id (example `module-search-loading`)
- Search test should assert filtered paginator collection values from `viewData('items')`.
- Sorting tests should assert:
  - asc/desc ordering with `sort_by` + `sort_direction`
  - invalid sort falls back to default ordering
  - descending column link clears sort params on next click
