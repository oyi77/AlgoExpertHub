---
inclusion: always
---

# Laravel Architecture Rules

## Framework & Version
- **Framework**: Laravel 9.x
- **PHP**: 8.0.2+
- **Architecture**: MVC with Service Layer pattern
- **Base Path**: `/home1/algotrad/public_html/main/`

## Directory Structure
```
main/
├── app/
│   ├── Adapters/       # External service adapters
│   ├── Console/        # Artisan commands
│   ├── Contracts/      # Interfaces
│   ├── DTOs/           # Data Transfer Objects
│   ├── Exceptions/     # Custom exceptions
│   ├── Helpers/        # Helper classes (Helper.php)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Backend/    # Admin controllers
│   │   │   ├── User/       # User controllers
│   │   │   └── Api/        # API controllers
│   │   ├── Middleware/
│   │   └── Requests/       # Form request validation
│   ├── Jobs/           # Queue jobs
│   ├── Mail/           # Mailable classes
│   ├── Models/         # Eloquent models
│   ├── Notifications/  # Notification classes
│   ├── Parsers/        # Message parsing logic
│   ├── Providers/      # Service providers
│   ├── Services/       # Business logic services
│   ├── Support/        # Support classes (AddonRegistry)
│   ├── Traits/         # Reusable traits
│   └── Utility/        # Utility classes (FormBuilder, ElementBuilder)
├── addons/             # Modular addon packages
├── bootstrap/          # Application bootstrap
├── config/             # Configuration files
├── database/
│   ├── migrations/     # Database migrations
│   ├── seeders/        # Database seeders
│   └── sql/            # SQL scripts
├── resources/
│   └── views/
│       ├── backend/    # Admin views
│       ├── frontend/   # User views (with themes)
│       └── emails/     # Email templates
├── routes/
│   ├── web.php         # User routes
│   ├── admin.php       # Admin routes
│   ├── api.php         # API routes
│   └── console.php     # Console routes
├── storage/            # Application storage
└── vendor/             # Composer dependencies
```

## Service Layer Pattern
- **ALL business logic MUST go in Service classes** under `app/Services/`
- Controllers should ONLY handle HTTP requests/responses and call services
- Services handle: validation, data transformation, database operations, external API calls
- Example pattern:
```php
// Controller
public function store(Request $request, SignalService $service)
{
    $result = $service->create($request);
    return redirect()->back()->with($result['type'], $result['message']);
}

// Service
public function create($request): array
{
    // Business logic here
    return ['type' => 'success', 'message' => 'Created successfully'];
}
```

## Controller Conventions
- **Backend Controllers**: `App\Http\Controllers\Backend\*` (admin panel)
- **User Controllers**: `App\Http\Controllers\User\*` or root (user panel)
- **API Controllers**: `App\Http\Controllers\Api\*`
- Controllers use dependency injection for services
- Return format: `['type' => 'success|error', 'message' => '...', 'data' => ...]`

## Routing Conventions
- **Admin routes**: Prefix `/admin`, middleware `['admin', 'demo']`, routes in `routes/admin.php`
- **User routes**: Prefix `/user` or root, middleware `['auth', 'inactive', 'is_email_verified', '2fa', 'kyc']`, routes in `routes/web.php`
- **API routes**: Prefix `/api`, routes in `routes/api.php`
- **Route naming**: `admin.resource.action` or `user.resource.action`

## Middleware Stack
- **Admin**: `admin` (RedirectIfNotAdmin)
- **User**: `auth`, `inactive`, `is_email_verified`, `2fa`, `kyc`
- **Demo Mode**: `demo` (prevents destructive actions)
- **Permissions**: `permission:permission-name,admin` (Spatie permissions)
- **Registration**: `reg_off` (registration toggle)

## View Theming System
- Multiple frontend themes: `default`, `blue`, `light`
- Theme selection: `Helper::theme()` returns active theme path
- View path pattern: `resources/views/frontend/{theme}/path/to/view.blade.php`
- Backend views: `resources/views/backend/`
- Always use `Helper::theme()` for user-facing views

## Helper Class Usage
- **Main Helper**: `App\Helpers\Helper\Helper`
- **File operations**: 
  - `Helper::filePath($type, $createIfNotExist)` - Get file path for type
  - `Helper::saveImage($file, $path)` - Save uploaded image
  - `Helper::getFile($type, $filename, $absolute)` - Get file URL
- **Theme**: `Helper::theme()` - Get active theme path
- **Configuration**: Access via `Configuration` model or `config()`

## Form Requests & Validation
- Use Form Request classes for validation: `app/Http/Requests/`
- Inject into controller methods
- Example: `public function store(SignalRequest $request)`
- Custom validation rules in service layer when needed

## Queue & Jobs
- **Queue Driver**: Database (or Redis for production)
- **Job Location**: `app/Jobs/`
- **Dispatch Pattern**: `dispatch(new JobClass($data))`
- **Important Jobs**:
  - `ProcessChannelMessage` - Process incoming channel messages
  - `SendEmailJob` - Send emails
  - `SendSubscriberMail` - Send subscriber newsletters
- Always queue long-running or external API operations

## Database Conventions
- **Migrations**: Timestamped files in `database/migrations/`
- **Seeders**: `database/seeders/`
- **Models**: `app/Models/`, singular, PascalCase
- **Tables**: Plural, snake_case
- **Foreign keys**: `{table}_id` (e.g., `user_id`, `signal_id`)
- **Pivot tables**: `{table1}_{table2}` alphabetically (e.g., `plan_signals`)
- **Timestamps**: Always include `created_at`, `updated_at`
- **Soft deletes**: Use when needed (`deleted_at`)

## Model Conventions
- Extend `Illuminate\Database\Eloquent\Model`
- Use `HasFactory` trait
- Use custom `Searchable` trait for search functionality
- Define relationships clearly (hasMany, belongsTo, belongsToMany)
- Use `$casts` for type casting (dates, JSON, arrays, booleans)
- Use `$guarded = []` or explicit `$fillable` 
- **Boot methods**: Use for auto-generating IDs, setting defaults
```php
protected static function booted()
{
    static::creating(function ($model) {
        if (!$model->getKey()) {
            $model->id = rand(1111111, 99999999);
        }
    });
}
```

## Configuration Files
- `config/app.php` - Application config, providers, aliases
- `config/auth.php` - Authentication guards (web, admin)
- `config/database.php` - Database connections
- `config/queue.php` - Queue configuration
- `config/permission.php` - Spatie permission config
- `config/services.php` - Third-party service credentials
- Custom configs: `config/installer.php`, `config/section.php`, etc.

## Dependency Injection
- Use constructor injection for services and dependencies
- Laravel's service container handles resolution automatically
- Example:
```php
protected $signalService;

public function __construct(SignalService $signalService)
{
    $this->signalService = $signalService;
}
```

## Error Handling
- Custom exception handler: `app/Exceptions/Handler.php`
- Use try-catch in services, return error arrays
- Never expose sensitive errors to users
- Log errors: `Log::error($message, $context)`
- Use `abort()` for HTTP exceptions

## Logging
- Laravel's logging: `Log::info()`, `Log::error()`, `Log::warning()`
- Channel: Single file (`storage/logs/laravel.log`)
- Log important events: job failures, API errors, security events
- Include context: `Log::error('Message', ['key' => 'value'])`

## Environment Variables
- All sensitive config in `.env` file
- Never commit `.env` to version control
- Use `env('KEY', 'default')` in config files ONLY
- Access config via `config('key')` in application code

## Asset Management
- **Frontend assets**: `asset/` directory (public)
- **Theme assets**: `asset/frontend/{theme}/`
- **Backend assets**: `asset/backend/`
- **User uploads**: Managed via `Helper::filePath()` and `Helper::saveImage()`

## Testing
- PHPUnit configured: `phpunit.xml`
- Tests directory: `tests/`
- Run: `php artisan test` or `./vendor/bin/phpunit`

## Artisan Commands
- Custom commands: `app/Console/Commands/`
- Register in `app/Console/Kernel.php`
- Scheduled tasks: Define in `Kernel::schedule()` method

## Best Practices
1. **Services for Business Logic** - Keep controllers thin
2. **Form Requests for Validation** - Don't validate in controllers
3. **Queue Long Operations** - Async processing for external APIs, emails
4. **Use Transactions** - Wrap multi-step DB operations in `DB::transaction()`
5. **Resource Controllers** - Use RESTful resource routes when possible
6. **Eager Loading** - Prevent N+1 queries with `with()`
7. **Caching** - Cache expensive queries and configurations
8. **Pagination** - Use `paginate()` for large datasets
9. **Soft Deletes** - For data that may need recovery
10. **Authorization** - Use policies or gate checks for permissions

