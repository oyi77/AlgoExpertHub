<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Resources

## Purpose
Frontend assets, Blade view templates, and internationalization files. Contains the presentation layer for both admin and user-facing interfaces, including 21 language directories, CSS/JS source files, email templates, and PWA assets.

## Key Files

| File | Purpose |
|------|---------|
| `css/tokens.css` | Design tokens (CSS custom properties) -- colors, spacing, typography |
| `css/trading-landing.css` | Trading landing page styles (~60KB) |
| `css/utilities.css` | Utility CSS classes |
| `css/app.css` | Main app stylesheet entry point |
| `js/app.js` | Main JS entry point |
| `js/bootstrap.js` | JS bootstrap (Axios, Laravel Echo setup) |
| `js/dialog-wrapper.js` | Dialog/modal wrapper component (~10KB) |
| `lang/en.json` | Primary English translations (~46KB) |
| `views/alert.blade.php` | Alert/notification component |
| `views/styleguide.blade.php` | Design system style guide |
| `views/swagger.blade.php` | Swagger API docs embed |

## Subdirectories

| Directory | Purpose |
|-----------|---------|
| `views/backend/` | Admin panel Blade templates |
| `views/frontend/` | Public-facing Blade templates |
| `views/components/` | Reusable Blade components |
| `views/partials/` | Partial templates (headers, footers, sidebars) |
| `views/emails/` | Email notification templates |
| `views/errors/` | Error page templates (404, 403, 500, etc.) |
| `views/pwa/` | Progressive Web App templates and manifest |
| `views/documentation/` | In-app documentation views |
| `views/scribe/` | Scribe-generated API documentation views |
| `views/vendor/` | Published vendor view overrides |
| `css/` | Source CSS files (compiled by Webpack/Mix) |
| `js/` | Source JavaScript files (compiled by Webpack/Mix) |
| `lang/` | 21 language directories: ar, de, en, es, et, fa, fr, gr, id, it, nl, pl, pt, pt-br, ro, ru, sections, th, tr, zh-CN, zh-TW. Plus JSON translation files |

## For AI Agents

### Working In This Directory
- Views use Blade templating: `@extends`, `@section`, `@component`, `@include`
- CSS is compiled via Webpack Mix (`main/webpack.mix.js`) -- edit source files in `css/` and `js/`, not compiled output
- Translations accessed via `__('key')` or `@lang('key')` -- add keys to `lang/en.json` and language-specific directories
- The `views/partials/` directory contains shared layout fragments
- PWA assets in `views/pwa/` support installable progressive web app

### Common Patterns
- Blade components in `views/components/` using `<x-component-name>` syntax
- Layout inheritance via `@extends('layouts.app')` / `@yield('content')`
- Translation strings support parameter substitution: `__('Welcome :name', ['name' => $user->name])`
- Design tokens in `tokens.css` provide the single source of truth for the design system
- Responsive design enforced via middleware (`ResponsiveDesignMiddleware`)

## Dependencies

### Internal
- `app/Http/Controllers/` -- Controllers pass data to views
- `config/view.php` -- View path configuration
- `webpack.mix.js` -- Asset compilation pipeline

### External
- `laravel/framework` -- Blade compiler
- `laravel-mix` (Webpack Mix) -- Asset compilation
- Bootstrap / Tailwind (check `package.json` for CSS framework)
- `laravel-echo` / Pusher -- Real-time JS client
