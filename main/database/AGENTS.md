<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Database

## Purpose
All database schema definitions, test/demo data, and raw SQL scripts. Contains 80 migrations spanning the full application schema (users, signals, plans, payments, trading, analytics, addons), 44 seeders for populating demo and configuration data, and factories for test data generation.

## Key Files

| File | Purpose |
|------|---------|
| `seeders/DatabaseSeeder.php` | Master seeder -- calls all seeders in dependency order with conditional addon seeder support |
| `seeders/ConfigurationSeeder.php` | Core app configuration values |
| `seeders/AdminSeeder.php` | Default admin user |
| `seeders/RolePermission.php` | Spatie permission roles and permissions |
| `sql/database.sql` | Full database dump for quick setup |

## Subdirectories

| Directory | Purpose |
|-----------|---------|
| `migrations/` | 80 Laravel migration files (2021-2025). Core tables: users, admins, signals, plans, payments, deposits, withdraws, tickets, gateways, configurations, languages, notifications, permissions, pages, contents, frontend_components |
| `seeders/` | 44 seeder classes. Ordered by dependency: config first, then gateways/content, then trading setup, then demo data (users, signals, payments), then addon features (AI, channels, backtesting, marketplace) |
| `factories/` | Model factories with Addons subdirectory for addon-specific factories. Used in tests and demo data generation |
| `sql/` | Raw SQL scripts: `database.sql` (full dump), `factory-state.sql`, and specific migration patches |

## For AI Agents

### Working In This Directory
- Always check seeder dependency order in `DatabaseSeeder.php` before adding new seeders
- Addon seeders use conditional loading: `class_exists()` + `AddonRegistry::active()` wrapped in try/catch
- Migration timestamps span 2021-2025 -- new migrations should use current timestamp
- Factories follow Laravel convention: `ModelNameFactory::class` with states for variants
- The `factories/Addons/` subdirectory contains addon-specific factory classes

### Common Patterns
- Migrations use `Schema::create()` with `id()`, `timestamps()`, `softDeletes()` where applicable
- Foreign keys use `->constrained()->cascadeOnDelete()` pattern
- Seeders extend `Illuminate\Database\Seeder` and use `$this->call()` for chaining
- Factories use `HasFactory` trait on models, invoked via `Model::factory()->create()`
- JSON columns used for flexible metadata fields (signal data, configuration values)

## Dependencies

### Internal
- `app/Models/` -- Every migration corresponds to a model; factories reference models
- `app/Support/AddonRegistry.php` -- Conditional addon seeder activation
- `addons/*/database/` -- Addon-specific migrations and seeders

### External
- `laravel/framework` -- Migration, seeder, factory base classes
- `spatie/laravel-permission` -- Permission tables migration
- Database engine: MySQL/MariaDB (based on JSON column usage and engine-specific features)
