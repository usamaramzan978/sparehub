# SpareHub User Guide

## 1. What SpareHub Does

SpareHub helps you run an auto spare parts + workshop business in one system:
- inventory and products
- workshop job cards
- sales/POS
- purchases and vendor payments
- daily expenses
- employee attendance and salaries
- daily business summary (End Of Day)

## 2. Login Areas

There are 2 login areas:
- System Admin: `/system/login`
- Tenant/Business User: `/firm/{your-tenant-id}/login`

If your owner/admin gives you credentials, use tenant login.

## 3. Roles in Simple Terms

Common roles:
- Tenant Owner: full control
- Admin: almost full control
- Manager: broad access with limited destructive actions
- Cashier: POS/sales/customer-focused access

Your visible menu depends on your assigned role permissions.

## 4. Main Tenant Sidebar Sections

Inside tenant panel, menu is organized as:
- Dashboard
- Sales
- Purchases
- Reports
- Workshop
- Master Data
- Expenses
- Employees
- System
- Support Tickets
- Account (Profile / Security / Logout)

## 5. First-Time Setup (Recommended Order)

1. Master Data > Branches (verify main branch)
2. Master Data > Units / Taxes
3. Master Data > Categories / Brands
4. Master Data > Products (prices are managed in product create/edit)
5. Master Data > Customers / Vehicles / Vendors
6. Employees > Users (if more staff needed)

## 6. Daily Operations

### 6.1 Sales (Counter/POS)

- Use `Sales > POS` for quick billing.
- You can also manage full invoices in `Sales > Sales Invoices`.
- In `Sales Invoices`, if you select a `Job Card`, SpareHub auto-loads that job's service + part lines into invoice items.
- Record collections in `Sales > Sale Payments`.
- Handle customer product returns in `Sales > Sales Returns` (returned qty goes back to stock).

Sales Invoices vs Sale Payments:
- `Sales Invoices`: this is the bill itself (items/services, totals, tax, discount, grand total, paid, balance).
- `Sale Payments`: this is money received against an invoice (cash/online/partial collections with date/reference).
- One invoice can have multiple payment entries until balance becomes `0`.
- Use invoice screen to define what customer bought; use sale payments screen to track what customer actually paid and when.

Practical payment flow (important):
- `cash` sale at POS:
  - invoice is created
  - payment is recorded immediately
  - invoice is effectively `Paid` when balance is `0`
- `online` sale at POS:
  - invoice is created
  - payment is recorded immediately (online/bank flow)
  - proof can be attached for online payment
- `debit` / partial sale at POS:
  - invoice is created first
  - if customer pays some amount now, that payment is recorded
  - remaining amount stays in invoice balance
  - later collections are added from `Sales > Sale Payments`
- `Print Not Paid` at POS:
  - invoice is saved as `hold`
  - no payment is recorded
  - bill opens for printing immediately
  - printed bill shows `Not Paid` watermark
  - sales screens show that invoice as `Not Paid`

How to track recovery quickly:
- `Paid/Partial/Unpaid/Not Paid` status is visible in sales screens.
- invoices needing recovery are those with `Balance > 0`.
- collection method analysis (cash/bank/card/wallet/other) comes from `Sale Payments`.

Sales Returns purpose:
- Use this when customer returns sold product items (full or partial qty).
- System creates a return document and increases product stock again.
- Return qty cannot exceed the remaining sold quantity for that sale item.

### 6.2 Workshop Jobs

- Create a `Job Card` for customer vehicle/service work.
- Add service lines and part lines inside the same Job Card form.
- Convert related work into billing through sales flow.
  - Open `Sales > Sales Invoices`.
  - Select the related `Job Card`.
  - System auto-fills customer and invoice lines from that job card.

Workshop menu purpose:
- `Job Cards`: single workshop screen for job details + labor/services + parts usage.

### 6.3 Purchases & Stock

- Add purchase invoice in `Purchases`.
- Add line items with `Qty`, `Cost`, `MRP`, `Retail Price`, and `Wholesale Price`.
- Record supplier payments in `Vendor Payments`.
- Use returns screens when sending items back to vendor.
- If `Purchase No`, `Return No`, or `Payment No` is left empty on create, system auto-generates it.
- After create, these document numbers are locked (read-only on edit).

Practical purchase example:
1. Vendor `City Auto Supplier` sends invoice `PI-1004`.
2. You purchase:
   - `Brake Pad Set` qty `20` at cost `25.00`
   - `Engine Oil 1L` qty `48` at cost `6.50`
3. Save purchase and later record partial payment in `Vendor Payments`.
4. Tracked stock increases for those products automatically.
5. Product pricing is auto-synced from purchase item values (`cost`, `mrp`, `retail`, `wholesale`).

Stock behavior for tracked products (`Track Stock` enabled):
1. Create product with opening stock.
2. Sales reduce stock.
3. Purchases increase stock.
4. Purchase returns reduce stock.

You can review stock movement history from:
- `Master Data > Products > History`

Opening stock is managed from product create/edit.

Product price safety on create/edit:
- system auto-validates prices while you type
- wholesale price cannot be less than purchase/cost
- retail price cannot be less than cost and cannot be greater than MRP
- MRP cannot be less than cost
- invalid price combinations are blocked on submit

### 6.6 Units and Fractional (Important)

Units explain quantity meaning for products.

Practical examples:
- Unit `Piece` (`is_fractional = No`): Spark Plug, Oil Filter, Brake Disc.
- Unit `Bottle` (`is_fractional = No`): Engine Oil 1L Bottle.
- Unit `Liter` (`is_fractional = Yes`): Bulk coolant.
- Unit `Kg` (`is_fractional = Yes`): Grease or packed material by weight.

Why this helps:
- Invoice clarity: `5 Piece`, `2 Bottle`, `1.5 Liter`.
- Staff clarity: no confusion about what quantity means.
- Better future control: whole-only vs decimal quantity policies.

Current behavior note:
- In current system, unit is mainly product context/label.
- Fractional flag exists but strict decimal/integer enforcement is not yet applied in all transactions.

### 6.7 Product History

Use `Master Data > Products > History` when you want a product audit trail.

It helps answer:
- which product quantity changed
- whether movement came from sale, purchase, return, or opening stock sync
- when the movement happened

### 6.8 Employees

- `Employee Attendances`: mark check-in/check-out/absent.
- `Employee Salaries`: track month-wise salary and paid status using:
  - per day salary
  - working days
  - bonus/deduction
  - net salary formula: `(per day salary × working days) + bonus - deduction`
- `Users`: maintain employee commission rules directly in user create/edit.

Commission rule fields:
- labour service (from Service Catalog where type is `labour`)
- total amount
- commission type (`fixed` or `percentage`)
- commission value

System shows payable totals and service payable details on each user profile.

### 6.9 Expenses

- Open `Expenses` from sidebar to record daily spending.
- Common examples: staff lunch, fuel, local transport, office misc.
- Add date, amount, payment method, and optional reference/notes.

## 7. End Of Day Page

Use `End Of Day` before closing business each day. It shows:
- sales and purchase totals
- cash in / cash out / net cash
- expenses total and entry count
- open job cards
- attendance summary
- payroll status for selected month

This helps owner/manager quickly review health of the day.

Practical end-of-day example:
- Sales today: `120,000`
- Purchase today: `35,000`
- Sale payments (cash in): `85,000`
- Vendor payments + expenses (cash out): `40,000`
- Net cash movement: `+45,000`
- Open job cards: `6`

Manager can immediately decide:
- whether cash is enough for tomorrow purchase
- whether pending job cards need extra mechanics

## 8. Multi-User in Same Tenant

Your tenant can have multiple users with separate credentials.

System Admin can create tenant users from:
- `System > Management > Tenant Users`

Each user logs in separately and can be assigned different permissions.

## 9. Branch Switching

If multiple branches exist, branch-sensitive records follow your current selected branch.
Switch branch from branch switch option in UI (session-based).

## 10. Profile & Settings

- Profile: update your personal account details.
- Settings: update company-level tenant settings (subject to permissions).

### 10.1 Customer Display

From `Settings > System Settings`, tenant admin can enable `Customer Display`.

How it works:
- Default state is disabled.
- When enabled, `Sales > POS` shows a `Customer Screen` button.
- Open that screen on a second monitor, second window, or second tab for customer-facing totals.
- The customer screen is read-only and mirrors the live POS cart/totals from the cashier screen.

Important notes:
- This first version works best when both cashier screen and customer screen are opened from the same browser on the same machine.
- If the setting is disabled, the `Customer Screen` button is hidden and the customer display URL is blocked.

### 10.2 Two-Factor Authentication (2FA)

From `Settings`, tenant admin can enable 2FA and choose method:
- `Email`: users receive a 6-digit code on login.
- `Authenticator App`: users enroll from `Profile > Security` and then use app-generated 6-digit code on login.

Notes:
- Settings controls policy only (`enable + method`) at tenant level.
- Authenticator setup/reset + backup codes are managed in `Profile > Security`.
- Login `/two-step` is verification-only; for authenticator it also allows backup code usage.

### 10.3 Tenant Timezone

From `Settings > Timezone`, tenant can choose its business timezone.

This affects:
- date/time display across tenant panel
- business day boundaries for reports/operations tied to tenant requests
- header digital clock (shown before POS button), which runs in tenant timezone

## 11. Basic Troubleshooting

- Cannot access page: ask admin to grant role permission.
- Login fails: verify tenant URL and credentials.
- Missing records: confirm you are on the correct branch.
- Report mismatch: verify date range and branch context.

## 12. Real-World Examples By Module

### 12.1 Dashboard

Example:
- At 9:00 AM, owner opens Dashboard and checks:
  - yesterday sales trend
  - top sold items
  - low stock warnings
- Based on this, owner decides to restock engine oil and brake pads.

### 12.2 Master Data

Example:
- Admin creates:
  - Unit: `Piece`
  - Tax: `VAT 18%`
  - Category: `Brakes`
  - Brand: `Bosch`
  - Product: `Brake Pad Set`, Track Stock = Yes
- This product is now ready for purchase and sale entries.

### 12.3 Workshop

Example:
- Customer arrives with `Toyota Corolla` for brake noise.
- Service advisor creates Job Card:
  - Service: `Brake Inspection`
  - Part: `Brake Pad Set` qty `1`
- Technician completes work and cashier converts job to invoice.

### 12.4 Sales / POS

Example:
- Cashier scans barcodes for:
  - `Oil Filter` x2
  - `Engine Oil 1L` x3
- System calculates totals/tax.
- Customer pays part cash today and remaining later.
- POS records immediate payment at checkout; later recovery is tracked in `Sale Payments`.

Not paid print flow:
- If customer wants the bill printed without taking payment yet, cashier clicks `Print Not Paid`.
- System saves the bill as `hold`.
- No payment entry is created at that time.
- Receipt prints with `Not Paid` watermark.
- Invoice appears as `Not Paid` in sales screens until payment is collected later.

Customer display flow:
- Owner/admin enables `Customer Display` from `Settings`.
- Cashier opens `Sales > POS`.
- Cashier clicks `Customer Screen`.
- That screen is moved to the second display and shows live cart items, totals, paid amount, change due, and balance due.

### 12.5 Purchases

Example:
- Store manager receives vendor bill from `City Auto Supplier`.
- Creates purchase:
  - `Spark Plug` x50
  - `Coolant` x20
- Saves purchase and records `30%` payment.
- Remaining amount appears in payable until fully paid.

### 12.6 Inventory / Stock

Example:
- Physical count shows system says `40` spark plugs but shelf has `38`.
- Storekeeper updates opening stock in product edit (if correction is needed).
- Manager verifies movement trail from `Master Data > Products > History`.

### 12.7 Expenses

Example:
- Daily expenses recorded:
  - Delivery fuel: `2,500`
  - Workshop cleaning: `1,200`
  - Tea/snacks: `600`
- End Of Day includes these in cash-out automatically.

### 12.8 Employees

Example:
- Morning: HR marks attendance (present/absent/late).
- Month-end: HR creates salary entries and marks paid/unpaid.
- Owner sees payroll pending in End Of Day summary.

### 12.9 Reports

Example:
- Manager opens:
  - `Reports > Sales` to review invoice performance
  - `Reports > Category Sales` to check which categories are running most
  - `Reports > Purchases` to review supplier buying
  - `Reports > Vendor Products` to check vendor-linked product performance
  - `Reports > Receivables` to follow up pending customer dues
- Exports each page as its own PDF for accountant.

### 12.10 Access Control

Example:
- Owner creates roles:
  - `Cashier`: access only POS + sale payments
  - `Store Manager`: purchases + inventory
  - `Workshop Advisor`: job cards only
- Each user sees only allowed menus and actions.

### 12.11 End Of Day

Example:
- Before closing, manager checks:
  - sales: `120,000`
  - purchases: `35,000`
  - cash in: `85,000`
  - cash out: `40,000`
  - net movement: `+45,000`
- Manager shares this summary with owner on WhatsApp/email.

### 12.12 Profile & Settings

Example:
- User updates personal phone and password in `Profile`.
- Owner updates business logo and company details in `Settings`.
- Owner can enable or disable the customer-facing POS display from `Settings > System Settings`.
- New invoice printouts and UI reflect updated business identity.

### 12.13 Branch Switching

Example:
- Company has `Lahore Branch` and `Islamabad Branch`.
- Manager switches branch from header switcher.
- All lists/reports now show only selected branch data.
