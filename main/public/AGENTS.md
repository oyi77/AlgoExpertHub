<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Public Web Root

## Purpose
The public-facing web root -- the only directory directly accessible by the web server. Contains the application entry point (`index.php`), compiled assets, cache clearing utility, and diagnostic tools. All other application code is outside this directory for security.

## Key Files

| File | Purpose |
|------|---------|
| `index.php` | Laravel application entry point -- loads autoloader, bootstraps app, handles HTTP request via kernel |
| `clear-cache.php` | Standalone cache clearing utility -- clears application, config, route, view, and OPcache |
| `websocket-test.html` | WebSocket connection testing page for real-time feature debugging |
| `ws-status.php` | WebSocket server status checker |

## Subdirectories

| Directory | Purpose |
|-----------|---------|
| `css/` | Compiled CSS output (from Webpack Mix) -- production stylesheets |
| `js/` | Compiled JavaScript output (from Webpack Mix) -- production scripts |
| `asset/` | Static assets: images, icons, fonts |

## For AI Agents

### Working In This Directory
- **Do not edit `index.php`** -- it is the standard Laravel entry point
- Compiled CSS/JS files in `css/` and `js/` are build outputs -- edit source files in `resources/css/` and `resources/js/` instead
- `clear-cache.php` is a standalone utility accessed directly via browser -- useful for production cache issues
- `websocket-test.html` is a debugging tool for testing WebSocket/Pusher connections
- New static files (images, downloads) go in `asset/`

### Common Patterns
- All PHP files use `require __DIR__ . '/../vendor/autoload.php'` pattern
- Compiled assets follow Laravel Mix naming: `app.css`, `app.js`, plus chunked files
- Web server (Nginx/Apache) document root points here
- `.htaccess` handles URL rewriting for clean URLs (if using Apache)

## Dependencies

### Internal
- `../bootstrap/app.php` -- Bootstraps the application (loaded by `index.php`)
- `../vendor/autoload.php` -- Composer autoloader
- `../resources/` -- Source files compiled into `css/` and `js/`

### External
- Web server (Nginx/OpenResty or Apache) -- Serves this directory
- PHP runtime -- Processes `index.php` and `*.php` files
- Laravel Octane (optional) -- Alternative high-performance server
