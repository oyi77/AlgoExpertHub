<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Tests

## Purpose
PHPUnit test suite with Feature and Unit test directories. Tests cover controllers, services, trading functionality, addons, and theme inheritance. Base `TestCase` uses `CreatesApplication` trait for bootstrapping.

## Key Files

| File | Purpose |
|------|---------|
| `TestCase.php` | Base test class -- extends Laravel's TestCase, uses `CreatesApplication` |
| `CreatesApplication.php` | Bootstraps the Laravel application for testing |
| `Feature/SignalControllerTest.php` | Signal controller endpoint tests |
| `Feature/SignalExecutionTest.php` | Signal execution flow tests |
| `Feature/SectionControllerTest.php` | Section/page controller tests |
| `Feature/ThemeInheritanceIntegrationTest.php` | Theme inheritance integration tests |
| `Feature/ExampleTest.php` | Example/smoke test |
| `Unit/AnalyticsServiceTest.php` | Analytics service unit tests |
| `Unit/GlobalConfigurationUnitTest.php` | Global configuration unit tests |
| `Unit/PositionServiceTest.php` | Position service unit tests |
| `Unit/ThemeManagerInheritanceTest.php` | Theme manager inheritance tests |
| `Unit/HelperThemeInheritanceTest.php` | Helper theme inheritance tests |

## Subdirectories

| Directory | Purpose |
|-----------|---------|
| `Feature/` | Integration/feature tests -- HTTP requests, controller behavior, full-stack flows |
| `Feature/Addons/` | Addon-specific feature tests |
| `Feature/Trading/` | Trading-specific feature tests |
| `Unit/` | Isolated unit tests -- services, helpers, individual class behavior |
| `Unit/Addons/` | Addon-specific unit tests |
| `Unit/Services/` | Service class unit tests |
| `Unit/Refactoring/` | Refactoring validation tests |
| `Property/` | Property-based tests |

## For AI Agents

### Working In This Directory
- Run tests with `php artisan test` or `vendor/bin/phpunit` from `main/` directory
- PHPUnit config is in `main/phpunit.xml`
- Feature tests use `RefreshDatabase` trait for clean database state
- Unit tests should mock dependencies and test in isolation
- Test naming: `it_can_do_something()` or `test_it_does_something()` (both patterns used)
- Addon tests in `Feature/Addons/` and `Unit/Addons/` test addon-specific functionality

### Common Patterns
- Tests extend `Tests\TestCase` (which uses `CreatesApplication`)
- `$this->get()`, `$this->post()`, `$this->put()`, `$this->delete()` for HTTP testing
- `$this->assertDatabaseHas()`, `$this->assertDatabaseMissing()` for database assertions
- Factories used for test data: `User::factory()->create()`
- `$this->actingAs($user)` for authenticated requests

## Dependencies

### Internal
- `app/` -- All production code under test
- `database/factories/` -- Factory classes for generating test data
- `database/seeders/` -- Seeders for baseline test data

### External
- `phpunit/phpunit` -- Test framework
- `laravel/framework` -- Testing helpers (RefreshDatabase, actingAs, HTTP testing)
- `laravel/telescope` -- Optional test profiling
