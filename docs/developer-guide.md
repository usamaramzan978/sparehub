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

## 4. Branch Scoping Logic

Branch scoping is session-driven and applied globally through `BranchScopedBySession` trait.

Behavior:
- On tenant routes, models using trait auto-filter by `session('tenant.current_branch_id')`.
- On save, if model has fillable `branch_id` and it is empty, branch is auto-injected.
- Scope is disabled for console context.

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

## 6. Data Model: Core Dependencies

Below is dependency-oriented mapping (parent -> child).

### 6.1 Tenant core

- `branches` -> `users`, `customers`, `vendors`, `warehouses`, `tenant_settings`, transactional tables
- `branches.warehouse_id` references a default warehouse
- `users.branch_id` nullable FK to `branches`

Seeders ensure a default branch + warehouse exists and linked.

### 6.2 Master data

- `categories.parent_id` self-reference
- `products` references:
  - `categories`
  - `brands`
  - `taxes` (default tax)
  - `units` (default unit)
- `product_prices` references `products` and optional `branches`
- `service_catalog` references `branches` and optional default `taxes`
- `warehouses` belongs to `branches`

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

### 6.5 Sales

- `sales` belongs to `branches`, optional `customers`, optional `job_cards`, optional creator `users`
- `sale_items` belongs to `sales`, `branches`, optional `products`, optional `service_catalog`, optional `job_card_services`
- `sale_payments` belongs to `sales`, `branches`, optional receiver `users`
- `sale_holds` belongs to `branches`, optional `customers`, optional creator `users`

### 6.6 Purchases

- `purchases` belongs to `branches`, optional `warehouses`, required `vendors`, optional creator `users`
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
- `stock_moves` belongs to `products`, `branches`, optional `warehouses`, optional creator `users`

#### Stock Movement Flow (`track_stock = true`)

This flow is complete for tracked products:
1. Product is created with opening stock / stock row.
2. Sale (`POS`, `Sales`, `Sale Items`) decrements stock.
3. Purchase (`Purchases`, `Purchase Items`) increments stock.
4. Purchase Return (`Purchase Returns`, `Purchase Return Items`) decrements stock back.

Notes:
- Stock is adjusted only when `products.track_stock = true`.
- Product listing shows current stock against opening stock (for example `17/20`).
- Opening stock + adjustments are managed from `Products > Stock` and `Products > Stock Adjustment`.

Important current implementation detail:
- `inventory_stocks` is branch-level only (no `warehouse_id` column).
- `stock_moves` supports `warehouse_id`, but most current stock writes set it to `null`.
- Result: warehouse is present in domain model and UI, but quantitative stock control is still branch-centric.

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
- default warehouse and branch linkage
- base taxes/units/categories/brands
- starter products/prices
- service catalog
- sample customer
- tenant settings

Critical ordering in bootstrap:
1. Branch
2. Warehouse using branch FK
3. Write `branches.warehouse_id`

This order prevents warehouse FK failures.

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
  Create purchase with warehouse/vendor and assert stock increment + payable creation.
- Workshop:
  Create job card, attach service and part lines, then verify billing linkage to sale.
- Inventory:
  Perform stock adjustment and verify `stock_moves` entry + updated `inventory_stocks`.
- Employees:
  Mark attendance and create salary record for same user/month branch context.
- Reports:
  Open per-report page (sales/purchases/etc.) and export page-specific PDF route.
