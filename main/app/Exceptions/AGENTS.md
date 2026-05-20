<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Exceptions

## Purpose
Centralized exception handling for the application. Contains the custom `Handler` class that extends Laravel's base exception handler to provide panel-aware error pages (admin, user, public), detailed error logging with user context, and graceful degradation for common failures.

## Key Files

| File | Purpose |
|---|---|
| `Handler.php` | Extends `Illuminate\Foundation\Exceptions\Handler`. Custom `report()` and `render()` methods with panel-aware error page routing, dual logging (Laravel Log + PHP error_log backup), and special handling for Page Builder and common 404s. |

## For AI Agents

### Working In This Directory
- The `Handler` provides panel-aware error rendering: detects admin (`admin/*` URL or admin guard), user (`Auth::check()`), or public/guest context
- Error views follow naming convention: `errors.{statusCode}`, `errors.{statusCode}-admin`, `errors.{statusCode}-user`
- Falls back to default Laravel error handling if custom views are unavailable
- Page Builder database connection errors are downgraded to warnings (not treated as application errors)
- Common 404s (`robots.txt`, `favicon.ico`, `sitemap.xml`, `.well-known`) are silently ignored in logging

### Common Patterns
- `report()`: Logs full context (user_id, admin_id, request URL/method, file, line, trace) via both `\Log::error()` and PHP `error_log()` as backup
- `render()`: Checks View service availability before attempting view rendering; returns plain text response if View is not bound
- Auth checks wrapped in try-catch guards to handle cases where auth service is unavailable during error handling
- `$dontFlash` excludes password fields from being shared in validation error responses
- Database errors (`QueryException`, `PDOException`) are caught and rendered as 500 with friendly pages

## Dependencies

### Internal
- `Illuminate\Foundation\Exceptions\Handler` - Base exception handler class
- Blade error views in `resources/views/errors/` (e.g., `500.blade.php`, `500-admin.blade.php`, `500-user.blade.php`, `404.blade.php`, etc.)

### External
- `symfony/http-kernel` - `NotFoundHttpException` and HTTP exception interface
- `illuminate/http` - Request object for URL/method inspection
- `illuminate/support` - Auth facade for guard checking
