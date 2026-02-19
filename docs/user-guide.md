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
- End Of Day
- Expenses
- Master Data
- Workshop
- Sales
- Purchases
- Access Control
- Employee
- Reports
- Settings / Profile

## 5. First-Time Setup (Recommended Order)

1. Master Data > Branches (verify main branch)
2. Master Data > Warehouses
3. Master Data > Units / Taxes
4. Master Data > Categories / Brands
5. Master Data > Products + Product Prices
6. Master Data > Customers / Vehicles / Vendors
7. Access Control > Users / Roles (if more staff needed)

## 6. Daily Operations

### 6.1 Sales (Counter/POS)

- Use `Sales > POS` for quick billing.
- You can also manage full invoices in `Sales > Sales Invoices`.
- Record collections in `Sales > Sale Payments`.

### 6.2 Workshop Jobs

- Create a `Job Card` for customer vehicle/service work.
- Add `Job Card Services` and `Job Card Parts`.
- Convert related work into billing through sales flow.

### 6.3 Purchases & Stock

- Add purchase invoice in `Purchases`.
- Add line items and taxes.
- Record supplier payments in `Vendor Payments`.
- Use returns screens when sending items back to vendor.

Stock behavior for tracked products (`Track Stock` enabled):
1. Create product with opening stock.
2. Sales reduce stock.
3. Purchases increase stock.
4. Purchase returns reduce stock.

You can review and adjust stock from:
- `Master Data > Products > Stock`
- `Master Data > Products > Stock Adjustment`

### 6.4 Employees

- `Employee Attendances`: mark check-in/check-out/absent.
- `Employee Salaries`: track month-wise salary and paid status.

### 6.5 Expenses

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

## 11. Basic Troubleshooting

- Cannot access page: ask admin to grant role permission.
- Login fails: verify tenant URL and credentials.
- Missing records: confirm you are on the correct branch.
- Report mismatch: verify date range and branch context.
