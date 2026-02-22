# SpareHub Implementation Roadmap

Use this checklist to implement improvements step by step.

## Phase 1: Reliability & Security Foundation

- [x] Add tenant context guard tests for critical flows:
  - login
  - POS save
  - reports
  - verify branch + timezone are always applied.

- [x] Add permission matrix tests for major modules:
  - Sales
  - Purchases
  - Settings
  - Users
  - prevent role/permission regressions.

- [x] Add audit timeline for sensitive actions:
  - 2FA changes
  - branch switch
  - settings updates
  - authentication failures.
  - expose timeline in tenant sidebar as `System -> Recent Activity`.

## Phase 2: Authentication & Account Security UX

- [x] Move authenticator enrollment to dedicated `Profile/Security` page.
- [x] Add authenticator reset secret flow.
- [x] Add backup/recovery codes flow.
- [x] Keep `/two-step` page verification-only.

## Phase 3: Timezone & Date Consistency

- [ ] Standardize date/time rendering with shared helper/component.
- [ ] Ensure all blade screens use tenant-local timezone output.
- [ ] Verify exports/reports respect tenant timezone boundaries.

## Phase 4: UI Maintainability & Performance

- [ ] Refactor `resources/views/layouts/shared/header.blade.php` into partials/components.
- [x] Cache header context data where safe:
  - tenant settings
  - branch name
  - role badge
  - reduce repeated query overhead.

## Phase 5: Operational Visibility & Release Safety

- [x] Add tenant health dashboard cards:
  - low stock
  - unpaid vendors
  - open job cards
  - failed login attempts.

- [ ] Add browser-level integration tests for primary journey:
  - login -> POS -> payment -> end of day.

- [x] Automate deployment safety checklist:
  - migrations
  - cache clear/warm
  - queue health checks
  - smoke endpoint checks.

## Suggested Execution Order

1. Phase 1
2. Phase 2
3. Phase 3
4. Phase 4
5. Phase 5

<!-- Add a small “tenant context guard” test suite for critical flows (login, POS save, reports) to ensure branch + timezone are always applied correctly.

Move authenticator enrollment into a dedicated Profile/Security page (with “reset secret” + backup codes) to avoid coupling enrollment with general settings.

Standardize date/time rendering in blades through one helper/component so all screens show tenant-local time consistently.

Add lightweight caching for header context (tenant settings, branch name, role badge) to reduce repeated per-request queries.

Introduce an activity/audit timeline for sensitive actions (2FA changes, branch switch, settings updates, auth failures).

Add permission matrix tests for major modules (Sales, Purchases, Settings, Users) to prevent regressions when roles evolve.

Create a “tenant health” dashboard card set (low stock, unpaid vendors, open job cards, failed logins) for operational visibility.

Clean and split header.blade.php into smaller partials/components to improve maintainability.

Add browser-level tests for your most used UX paths (login -> POS -> payment -> end of day) to catch UI integration issues early.

Add deployment checklist automation (migrate, cache clear/warm, queue health checks, smoke endpoints) for safer releases. -->
