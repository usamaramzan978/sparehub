# SpareHub

SpareHub is a Laravel 12 multi-tenant auto spare parts and workshop management system.

## Documentation

- Developer guide: `docs/developer-guide.md`
- User guide: `docs/user-guide.md`

## App Areas

- System CRM (central): `/system/*`
- Tenant panel (path-based tenancy): `/firm/{tenant}/*`

## Quick Start

1. Install dependencies:
```bash
composer install
npm install
```

2. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

3. Run migrations and seeders:
```bash
php artisan migrate
php artisan db:seed
```

4. Build frontend assets:
```bash
npm run build
```

For local development:
```bash
composer run dev
```

## Quality Checks

```bash
./vendor/bin/rector process
./vendor/bin/pint
./vendor/bin/phpstan analyse --memory-limit=2G
```

## Notes

- Tenant bootstrap is automated during tenant creation (database create, migrate, and default seed pipeline).
- Branch-specific data is session scoped inside tenant routes.
- Daily expense tracking is available at `tenant.expenses.*` and is included in End-of-Day cash-out metrics.
