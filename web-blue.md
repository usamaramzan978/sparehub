# Sparehub BluePrint v2 (Migration-First, Enterprise Multi-Tenant)

Target product: SaaS for bike auto-shops in Pakistan (POS + Workshop + Inventory + Accounts + Reports).

This version replaces feature-first notes with a **database-first execution plan** so engineering can start implementation immediately.
Important domain rule: a sale can include **products, services, or both** in one invoice (e.g., spare part + bike tuning + wheel alignment labor).

---

## 1) Final Architecture Decisions

### 1.1 Tenancy model

- Framework: `stancl/tenancy`
- Tenant isolation: **database-per-tenant**
- Tenant access URL: **single domain** (not tenant subdomains)
- Proposed URL strategy:
    - `/t/{tenant_slug}/...`
    - tenant resolved by first path segment + authenticated tenant guard

### 1.2 Database split

- **Central DB**
    - tenant registry, plans/subscriptions, platform admins, billing metadata, audit/ops logs
- **Tenant DB (one per tenant)**
    - full business data for that shop: users, inventory, workshop, POS, purchases, accounting artifacts

### 1.3 Primary key standard

- Use UUID on all domain tables.
- Rule: `uuid('id')->primary()` for entity tables.
- Foreign keys: `foreignUuid()` with explicit constraints.
- Avoid bigint IDs for business entities.

### 1.4 Core schema standards

- Monetary columns: `decimal(18, 2)`
- Quantity columns: `decimal(18, 3)`
- Tax rate: `decimal(7, 4)` (supports e.g. `18.0000`)
- Important transactional tables: include `created_by`, `updated_by`, optional `deleted_by`
- High-volume tables: add covering indexes for date + branch + status + reference numbers
- Add soft deletes where business recovery is needed

---

## 2) Gaps Found In Current Setup (must fix early)

1. Existing tenant schema is too small (`branches`, `users`, `customers`, `tenant_settings`) for your target modules.
2. `spatie/permission` tenant migration uses bigint IDs; this conflicts with your UUID standard.
3. `branches` model expects `warehouse_id`, but current migration doesn’t create warehouses first.
4. For single-domain tenancy, domain/subdomain identification should not be the primary resolver; route/path identification should be implemented.

---

## 3) Central DB Migration Plan (Platform)

Keep central DB minimal and operational.

### 3.1 Core central tables

1. `tenants`

- `id` UUID PK
- `name`, `slug` (unique), `status`, `timezone`, `currency`, `plan_id`
- `data` JSON nullable
- indexes: `slug`, `status`, `plan_id`

2. `domains` (optional fallback)

- Keep table for compatibility, but single-domain mode can use one fixed domain entry per tenant or be bypassed by path resolver.

3. `plans`

- billing plan metadata (name, limits JSON, price, cycle)

4. `subscriptions`

- tenant-plan relation, period dates, status, billing provider refs
- indexes: `(tenant_id, status)`, `(current_period_end)`

5. `system_users`

- platform operators/support staff

6. `tenant_provisioning_logs`

- tenant lifecycle audit (created DB, migrated, seeded, failed step, retry count)

---

## 4) Tenant DB Migration Plan (Business Domain)

Use ordered phases so `tenants:migrate` is deterministic and safe.

## Phase A: Identity, Branching, Settings

### A1 `branches`

- `id`, `code`, `name`, `phone`, `email`, `city`, `address`, `is_default`, `status`
- indexes: unique `code`, `(status)`, `(is_default)`

### A2 `users`

- `id`, `branch_id` nullable, `name`, `email`, `phone`, `password`, `status`, remember token, timestamps
- unique: `email`
- indexes: `phone`, `(branch_id, status)`

### A3 permissions/roles (Spatie, UUID edition)

- `permissions`: UUID PK
- `roles`: UUID PK
- pivot tables use UUID FKs (`role_id`, `permission_id`, `model_id` as UUID-compatible)
- add indexes for fast role resolution by model + guard

### A4 `tenant_settings`

- one row per branch or one global row (pick one pattern and stay consistent)
- if branch-scoped: unique `branch_id`
- include invoice print/footer, default tax mode, number format, notification flags

---

## Phase B: Master Data

### B1 `warehouses`

- `id`, `branch_id`, `code`, `name`, `status`
- unique: `(branch_id, code)`

### B2 `units`

- `id`, `code`, `name`, `is_fractional`
- unique `code`

### B3 `taxes`

- `id`, `code`, `name`, `rate`, `is_inclusive`, `status`
- unique `(code)`

### B4 `categories`

- `id`, `parent_id` nullable, `name`, `slug`, `status`
- unique `(slug)`
- index `parent_id`

### B5 `brands`

- `id`, `name`, `slug`, `status`
- unique `(slug)`

### B6 `products`

- `id`, `category_id`, `brand_id`, `default_tax_id`, `default_unit_id`
- `sku`, `part_number`, `barcode`, `name`, `description`, `track_stock`, `is_service_item`, `status`
- unique: `(sku)`, `(part_number)` nullable unique where supported, `(barcode)` nullable unique
- indexes: `name`, `(category_id, status)`, `(brand_id, status)`

### B7 `product_prices`

- `id`, `product_id`, `branch_id` nullable, `cost`, `mrp`, `retail_price`, `wholesale_price`, `effective_from`
- index `(product_id, branch_id, effective_from)`

### B8 `service_catalog`

- `id`, `branch_id`, `code`, `name`, `category`, `base_price`, `duration_minutes`, `default_tax_id`, `status`
- examples: bike tuning, wheel alignment, oil change, labor charge
- unique `(branch_id, code)`
- indexes `(branch_id, status)`, `(name)`

---

## Phase C: Inventory Engine

### C1 `inventory_stocks`

- current balance table per stock point
- columns: `id`, `product_id`, `branch_id`, `warehouse_id`, `qty_on_hand`, `qty_reserved`, `avg_cost`
- unique: `(product_id, branch_id, warehouse_id)`

### C2 `stock_moves`

- immutable movement ledger
- columns: `id`, `product_id`, `branch_id`, `warehouse_id`, `move_type`, `qty`, `unit_cost`, `total_cost`, `reference_type`, `reference_id`, `occurred_at`, `created_by`
- indexes:
    - `(product_id, occurred_at)`
    - `(branch_id, occurred_at)`
    - `(reference_type, reference_id)`

### C3 `stock_adjustments` + `stock_adjustment_items`

- physical count corrections with approval trail
- indexes on `adjustment_no`, `(branch_id, adjustment_date)`, `(status)`

### C4 `stock_transfers` + `stock_transfer_items`

- warehouse/branch transfer workflow (draft/in_transit/received/cancelled)

---

## Phase D: Customers, Vendors, Vehicles, Jobs

### D1 `customers`

- `id`, `branch_id`, `code`, `name`, `phone`, `email`, `cnic`, `ntn`, `address`, `city`, `credit_limit`, `opening_balance`, `status`
- unique `(branch_id, code)`
- indexes `phone`, `name`, `(branch_id, status)`

### D2 `vendors`

- similar to customers with payable-specific fields

### D3 `customer_vehicles`

- `id`, `customer_id`, `registration_no`, `model`, `year`, `chassis_no`, `engine_no`, `meter_reading`
- unique `(registration_no)`
- indexes `(customer_id)`, `(model)`

### D4 `job_cards`

- `id`, `branch_id`, `job_no`, `customer_id`, `vehicle_id`, `assigned_employee_id`, `job_date`, `status`, meter fields, visit counters, remarks
- unique `(branch_id, job_no)`
- indexes `(status, job_date)`, `(vehicle_id, job_date)`, `(customer_id, job_date)`

### D5 `job_card_services`

- service lines per job card (from `service_catalog` or custom line)
- include `service_catalog_id` nullable, `service_name`, `technician_id`, `qty`, `rate`, `line_total`, `status`
- indexes `(job_card_id)`, `(technician_id, status)`

### D6 `job_card_parts`

- optional parts consumed before invoice

---

## Phase E: Sales/POS/Returns

### E1 `sales`

- invoice header
- `id`, `branch_id`, `invoice_no`, `invoice_date`, `customer_id`, `job_card_id` nullable, `status`, totals (gross/discount/tax/net), payment summary, posted flags
- `invoice_type`: `product`, `service`, `mixed` (or derive from item lines)
- unique `(branch_id, invoice_no)`
- indexes:
    - `(branch_id, invoice_date)`
    - `(customer_id, invoice_date)`
    - `(status, invoice_date)`

### E2 `sale_items`

- invoice lines with explicit line type:
    - `line_type`: `product` or `service`
    - `product_id` nullable (for parts/items)
    - `service_catalog_id` nullable (for standard services like tuning/alignment)
    - `job_card_service_id` nullable (when billing completed job services)
    - qty, unit price, discount, tax, line totals
- constraint rule: at least one of `product_id`, `service_catalog_id`, `job_card_service_id` must be present based on `line_type`
- indexes `(sale_id)`, `(product_id)`, `(branch_id, product_id, created_at)`
- additional indexes `(service_catalog_id)`, `(job_card_service_id)`, `(line_type, created_at)`

### E3 `sale_payments`

- payment events against sale
- indexes `(sale_id, paid_at)`, `(payment_method_id, paid_at)`

### E4 `sales_returns` + `sales_return_items`

- return workflow with references to original sale and stock impact

### E5 `sale_holds`

- temporary hold carts for POS recovery
- index `(branch_id, created_at)`

---

## Phase F: Purchases/Payables

### F1 `purchases` + `purchase_items`

- vendor invoices, landed costs, stock receiving status
- unique `(branch_id, purchase_no)`

### F2 `purchase_returns` + `purchase_return_items`

### F3 `vendor_payments`

- payment history against purchases/open balance

---

## Phase G: Accounting Support (Operational, Not Full GL Initially)

### G1 `ledger_accounts` (optional in MVP)

### G2 `ledger_entries`

- generic receivable/payable/cash movements
- indexes `(party_type, party_id, entry_date)`, `(branch_id, entry_date)`

### G3 `cash_registers` and `register_sessions`

- open/close counter cash accountability

### G4 `expenses` and `expense_categories`

---

## Phase H: System Tables

### H1 `number_sequences`

- per-branch sequences for `invoice_no`, `job_no`, `purchase_no`, return numbers
- unique `(branch_id, sequence_key)`

### H2 `attachments`

- if using media-library directly, keep package table; otherwise map refs for easy querying

### H3 `audit_logs`

- actor, action, entity type/id, old/new JSON, IP/user_agent, timestamp
- indexes `(entity_type, entity_id)`, `(actor_id, created_at)`, `(created_at)`

### H4 `reminders`

- due-date tasks from jobs/sales with priority/status

---

## 5) Migration Filename Convention (Tenant DB)

Use deterministic sequence style:

- `2026_02_20_000100_create_branches_table.php`
- `2026_02_20_000110_create_users_table.php`
- `2026_02_20_000120_create_permission_tables_uuid.php`
- ...

Rules:

- Keep one bounded context per migration file.
- Keep FK dependencies ordered (parent first).
- For heavy modules, split header/items tables into separate files.

---

## 6) Indexing Standards (Enterprise)

### 6.1 Always add indexes for

- Foreign keys
- Frequent filters (`status`, `branch_id`, `date`)
- Document lookups (`invoice_no`, `job_no`, `purchase_no`, `registration_no`)
- Pivot model queries (`model_type`, `model_id`)

### 6.2 Composite index examples

- sales listing: `(branch_id, invoice_date, status)`
- job board: `(branch_id, status, job_date)`
- stock history: `(product_id, branch_id, occurred_at)`
- customer statement: `(customer_id, entry_date)`

### 6.3 Unique constraints examples

- `(branch_id, invoice_no)`
- `(branch_id, job_no)`
- `(branch_id, code)` for customer/vendor/product human codes

---

## 7) UUID + Spatie Permission Alignment

To keep UUID everywhere:

1. Publish and customize permission migration in tenant path.
2. Change `roles`/`permissions` PKs to UUID.
3. Change pivot FKs to UUID.
4. Set `model_morph_key` in `config/permission.php` to UUID-compatible name if needed (e.g. `model_id` but stored as UUID field type).
5. Ensure tenant `User` model uses `HasRoles` and UUID trait.

---

## 8) Media Library in Tenant DB

- Keep `media` table in tenant migrations path (already present).
- Use UUID PK on media if you want consistency; otherwise keep package default and ensure all model references work.
- Indexes to keep:
    - `(model_type, model_id)`
    - `(collection_name)`
    - `(order_column)`

---

## 9) Single-Domain Tenant Identification

Because you want one domain for all tenants:

- Recommended middleware flow:
    - Read `{tenant_slug}` from route prefix `/t/{tenant_slug}`
    - Resolve tenant from central `tenants.slug`
    - Initialize tenancy
    - Continue request in tenant context

Benefits:

- No wildcard DNS complexity
- Easy local/dev/testing
- Works for Pakistan local-market rollout where shops share one product domain

---

## 10) Execution Order (What to build now)

1. Refactor existing tenant migrations into ordered phased files (A to H above).
2. Replace Spatie permission migration with UUID version for tenant DB.
3. Implement single-domain tenant resolver middleware (`/t/{tenant_slug}`).
4. Add sequence service backed by `number_sequences` table.
5. Deliver first vertical slice:

- Product
- Stock movement
- Sales invoice + sale_items + payment
- Customer ledger entry

---

## 11) Non-Negotiable Quality Rules

- No table without explicit indexing strategy.
- No business document table without status + audit columns.
- No transactional write without corresponding stock/ledger impact design.
- No direct delete on financial/stock docs (use status reversal or soft delete policy).
- Every migration must have safe `down()` and pass fresh migrate on empty DB.

---

## 12) Next Step in Codebase

Start by replacing these tenant migration files first:

- `database/migrations/tenant/2026_02_04_010000_create_tenant_core_tables.php`
- `database/migrations/tenant/2026_02_16_034959_create_permission_tables.php`

Then add phased migration files for master data, inventory, workshop, sales, purchases, and ledger support.
