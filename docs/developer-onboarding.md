# Developer Onboarding Guide

Welcome to the AlgoExpertHub Trading Platform development team! This guide will help you get up to speed quickly.

## Quick Start (15 Minutes)

### 1. Clone and Setup
```bash
git clone <repository-url>
cd public_html/main
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### 2. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 3. Run Development Server
```bash
php artisan serve
npm run dev
```

### 4. Access Platform
- **Frontend**: http://localhost:8000
- **Admin**: http://localhost:8000/admin
- **Default Admin**: admin@admin.com / password

---

## Project Overview

### Technology Stack
- **Framework**: Laravel 10.x
- **PHP**: 8.1+
- **Database**: MySQL
- **Frontend**: Blade, Bootstrap, jQuery
- **Queue**: Database/Redis
- **Cache**: File/Redis

### Architecture
- **MVC with Service Layer**: Controllers thin, business logic in services
- **Addon System**: Modular features as self-contained packages
- **Event-Driven**: Observers and events for loose coupling

---

## Essential Reading

### Must-Read Documentation (30 Minutes)
1. **AGENTS.md** - Development workflow and commands
2. **.cursor/rules/README.mdc** - Architecture overview
3. **.cursor/rules/laravel-architecture.mdc** - Laravel patterns
4. **.cursor/rules/addon-system.mdc** - Addon development

### Key Concepts
- **Service Layer Pattern**: All business logic in `app/Services/`
- **Addon Architecture**: Features in `addons/` directory
- **Response Format**: `['type' => 'success|error', 'message' => '...']`
- **Theme System**: `Helper::theme()` for dynamic views

---

## Development Workflow

### 1. Understanding the Codebase

#### Directory Structure
```
main/
├── app/
│   ├── Http/Controllers/    # HTTP handling only
│   ├── Services/             # Business logic HERE
│   ├── Models/               # Eloquent models
│   └── Jobs/                 # Background jobs
├── addons/                   # Modular features
├── resources/views/          # Blade templates
└── routes/                   # Route definitions
```

#### Core Principles
- **SOLID**: Single responsibility, dependency injection
- **DRY**: Don't repeat yourself
- **Service Layer**: Controllers delegate to services
- **Queue Long Operations**: External APIs, emails

### 2. Making Changes

#### Feature Development Process
1. **Plan**: Review specs in `specs/active/`
2. **Create Service**: Business logic in `app/Services/`
3. **Create Controller**: Thin HTTP handler
4. **Create Views**: Blade templates
5. **Add Routes**: In appropriate route file
6. **Write Tests**: Feature and unit tests
7. **Test Locally**: Verify functionality
8. **Submit PR**: Code review

#### Code Style
- **PSR-12**: Follow PHP coding standards
- **Type Hints**: Always use parameter and return types
- **Strict Types**: `declare(strict_types=1);`
- **Documentation**: PHPDoc for all public methods

### 3. Testing

#### Running Tests
```bash
php artisan test                    # All tests
php artisan test --filter SignalTest  # Specific test
```

#### Writing Tests
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SignalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_signal()
    {
        $admin = Admin::factory()->create();
        
        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/signals', [
                'title' => 'Test Signal',
                'currency_pair_id' => 1,
                // ...
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('signals', ['title' => 'Test Signal']);
    }
}
```

---

## Common Tasks

### Creating a New Feature

#### 1. Create Service
```php
<?php

namespace App\Services;

class MyFeatureService
{
    public function create(array $data): array
    {
        try {
            // Business logic here
            
            return [
                'type' => 'success',
                'message' => 'Created successfully',
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
```

#### 2. Create Controller
```php
<?php

namespace App\Http\Controllers\Backend;

use App\Services\MyFeatureService;
use Illuminate\Http\Request;

class MyFeatureController extends Controller
{
    public function __construct(
        private MyFeatureService $service
    ) {}

    public function store(Request $request)
    {
        $result = $this->service->create($request->all());
        
        return redirect()->back()
            ->with($result['type'], $result['message']);
    }
}
```

#### 3. Add Routes
```php
// routes/admin.php
Route::middleware(['admin', 'demo'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('my-feature', MyFeatureController::class);
});
```

### Creating a Migration
```bash
php artisan make:migration create_my_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('my_table', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('my_table');
    }
};
```

### Creating a Job
```bash
php artisan make:job ProcessSomething
```

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSomething implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $id
    ) {}

    public function handle()
    {
        // Job logic here
    }
}
```

---

## Addon Development

### Creating an Addon

#### 1. Create Directory Structure
```bash
mkdir -p addons/my-addon/app/{Http/Controllers,Services,Models}
mkdir -p addons/my-addon/{routes,resources/views,database/migrations}
```

#### 2. Create addon.json
```json
{
    "name": "my-addon",
    "title": "My Addon",
    "description": "Description of my addon",
    "version": "1.0.0",
    "author": "Your Name",
    "namespace": "Addons\\MyAddon",
    "status": "active",
    "modules": [
        {
            "key": "admin_ui",
            "name": "Admin Interface",
            "enabled": true
        }
    ]
}
```

#### 3. Create Service Provider
```php
<?php

namespace Addons\MyAddon;

use Illuminate\Support\ServiceProvider;

class MyAddonServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register services
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'my-addon');
        $this->loadRoutesFrom(__DIR__ . '/routes/admin.php');
    }
}
```

#### 4. Register in AppServiceProvider
```php
// app/Providers/AppServiceProvider.php
protected function registerAddonServiceProviders(): void
{
    $addonProviders = [
        'my-addon' => \Addons\MyAddon\MyAddonServiceProvider::class,
    ];

    foreach ($addonProviders as $slug => $provider) {
        if (class_exists($provider)) {
            $this->app->register($provider);
        }
    }
}
```

---

## Debugging

### Enable Debug Mode
```env
APP_DEBUG=true
APP_ENV=local
```

### Useful Commands
```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# View logs
tail -f storage/logs/laravel.log

# Database inspection
php artisan tinker
>>> User::count()
>>> Signal::latest()->first()

# Queue inspection
php artisan queue:work --once
php artisan queue:failed
```

### Laravel Debugbar
```bash
composer require barryvdh/laravel-debugbar --dev
```

---

## Best Practices

### Code Quality
- ✅ Write self-documenting code
- ✅ Use meaningful variable names
- ✅ Keep methods small (< 20 lines)
- ✅ One responsibility per class
- ✅ Type hint everything

### Security
- ✅ Validate all input (Form Requests)
- ✅ Sanitize output (Blade auto-escapes)
- ✅ Encrypt sensitive data (`encrypt()`)
- ✅ Use middleware for auth/permissions
- ✅ Never trust user input

### Performance
- ✅ Eager load relationships (`with()`)
- ✅ Queue long operations
- ✅ Cache expensive queries
- ✅ Index database columns
- ✅ Paginate large datasets

---

## Resources

### Documentation
- **Laravel**: https://laravel.com/docs/10.x
- **Project Docs**: `/docs` directory
- **Rule Files**: `/.cursor/rules`

### Tools
- **PHPStorm**: IDE with Laravel plugin
- **TablePlus**: Database GUI
- **Postman**: API testing
- **Git**: Version control

### Community
- **Slack**: Team chat
- **GitHub**: Code repository
- **Wiki**: Internal documentation

---

## Getting Help

1. **Check Documentation**: Rule files and docs
2. **Search Codebase**: Similar implementations
3. **Ask Team**: Slack or meetings
4. **Submit Issue**: GitHub issues

---

**Welcome aboard! Happy coding! 🚀**
