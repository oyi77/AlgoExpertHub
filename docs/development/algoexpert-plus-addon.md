# AlgoExpert++ Addon

**Version:** 1.0.0
**Slug:** `algoexpert-plus-addon`

The AlgoExpert++ Addon is a comprehensive modular integration layer that enhances the application with essential system capabilities, including SEO tools, Queue management, System Health monitoring, and Backups.

## Modules

The addon is composed of several independent modules that can be enabled or disabled via `addon.json`.

### 1. SEO Integration
**Key:** `seo`
- **Dependency:** `artesaos/seotools`
- **Functionality**: Provides tools to manage meta tags, OpenGraph data, and Twitter cards.
- **Usage**: Automatically registers the `SEOToolsServiceProvider`.

### 2. Queues Dashboard (Horizon)
**Key:** `queues`
- **Dependency:** `laravel/horizon`
- **Functionality**: Provides a beautiful dashboard and code-driven configuration for your Laravel Redis queues.
- **Access**: Restricted to Super Admins via `/horizon` (implicit access control configured in `AddonServiceProvider`).

### 3. System Backups
**Key:** `backup`
- **Dependency:** `spatie/laravel-backup`
- **Functionality**: Automates file and database backups.
- **Configuration**: Uses standard Laravel backup configuration.

### 4. System Health
**Key:** `health`
- **Dependency:** `spatie/laravel-health`
- **Functionality**: Monitors various aspects of the application (disk space, database connection, cache, etc.).
- **Services**: `SystemHealthService` provides status checks.

### 5. Internationalization (i18n)
**Key:** `i18n`
- **Functionality**: basic locale configuration helper.
- **Behavior**: Sets the application locale based on configuration when enabled.

## Configuration

### Activating Modules
Modules are controlled via the `manifest` in `addon.json`:

```json
"modules": [
    {
        "key": "seo",
        "name": "SEO Integration",
        "enabled": true
    },
    ...
]
```

### Services & Dependency Injection

The addon registers several singleton services for use within the application:

- `Addons\AlgoExpertPlus\App\Services\BackupService`
- `Addons\AlgoExpertPlus\App\Services\HealthService`
- `Addons\AlgoExpertPlus\App\Services\SeoService`
- `Addons\AlgoExpertPlus\App\Services\I18nService`

## Admin Interface

The addon provides an admin interface (accessible if `admin_ui` target is enabled for modules) to view status and manage these system services.

**Routes:**
- Prefix: `/admin/algoexpert-plus`
- Middleware: `web`, `admin`, `demo`
