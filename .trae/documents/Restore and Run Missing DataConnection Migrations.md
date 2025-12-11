# Analysis of 500 Internal Server Error on General Settings

## Root Cause
The 500 Internal Server Error is caused by a missing database table: `sp_data_connections`.
- **Log Evidence:** `Base table or view not found: 1146 Table 'algotrad_signals.sp_data_connections' doesn't exist`.
- **Code Trace:**
  - The error occurs when a request touches code that relies on `Addons\TradingManagement\Modules\DataProvider\Models\DataConnection`.
  - The `DataConnection` model is used extensively in `TradingBotController` and other trading-related modules.
  - Crucially, the migration for this table exists in `addons/trading-management-addon/database/migrations_backup/2025_12_04_100000_create_data_connections_table.php`, but it seems it **has not been run** or is in a `migrations_backup` folder rather than the active migrations path.
  - The `artisan migrate:status` output confirms that while many 2025 migrations ran, `2025_12_04_100000_create_data_connections_table` is **not** in the list of executed migrations.

## Why "General Settings" Fails
Even though `ConfigurationController@ConfigurationUpdate` (the target of the failing request) handles general settings, the application likely has a **global middleware**, **service provider**, or **event listener** (like `PositionUpdated` seen in logs) that attempts to load `DataConnection` models on every request or specifically on administrative POST requests (perhaps for validation or logging). When this model query runs, it fails because the table is missing.

## Resolution Plan
We need to manually run the migration to create the `data_connections` table (and its logs table). Since the migration file is in a `migrations_backup` directory, we should move it to the active migrations folder or run it directly.

### Steps:
1.  **Restore Migrations:** Copy/Move the required migration files from `main/addons/trading-management-addon/database/migrations_backup/` to `main/database/migrations/` (or the addon's active migration folder).
    - `2025_12_04_100000_create_data_connections_table.php`
    - `2025_12_04_100001_create_market_data_table.php` (likely needed too)
    - `2025_12_04_100002_create_data_connection_logs_table.php`
2.  **Run Migrations:** Execute `php artisan migrate` inside the container.
3.  **Verify:** Check if the table `sp_data_connections` exists and if the Settings page now saves correctly.

## Note on Table Prefix
The `.env` and `config/database.php` confirm the database prefix is `sp_`. Thus, the migration creating `data_connections` will correctly result in `sp_data_connections` in the database.

I will proceed to copy the migration files and run them.
