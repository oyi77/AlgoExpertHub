# Draft: Theming Engine Refactor

## Requirements (confirmed)
- Goal: create a refactor plan for the theming engine to reduce redundancy and improve organization and functionality.
- Working themes: `trading-v1`, `default`, and `beta-ui` (beta UI is working; do not touch beta UI).
- Approach preference: Consolidate resolver + APIs.
- Scope preference: Include Blade template updates to replace theme concatenations.
- Constraint: Do not touch `user.beta.*` routes or `main/resources/views/frontend/trading-v1/**`.

## Technical Decisions
- Use `Helper::themeView()` in PHP and `Config::themeView()` in Blade as canonical resolution APIs.
- Consolidate theme management around `ConfigurationController` + `HandlesThemeManagement` with `ThemeManager` as source of truth.
- Treat `ThemeApiController`/`ThemeManagementController` as API surfaces only if routed; otherwise leave as legacy.

## Research Findings
- `ThemeManager` manages inheritance and theme metadata: `main/app/Services/ThemeManager.php`.
- `Helper::themeView()` performs inheritance-based resolution: `main/app/Helpers/Helper.php`.
- Theme admin routes are defined in `main/routes/admin/system.php` and point to `ConfigurationController` + `HandlesThemeManagement`.
- `ThemeApiController` uses `resource_path('views/themes')` and `config('app.theme')`, which conflicts with frontend theme storage.
- `ThemeManagementController` proxies to `ConfigurationController` and lists themes from `views/frontend`.
- `Config` alias points to `App\Helpers\Helper\Helper` (used in Blade as `Config::themeView`).
- Frontend themes located under `main/resources/views/frontend/{theme}`.

## Open Questions
- None (beta UI is explicitly out of scope).

## Scope Boundaries
- INCLUDE: Theming engine consolidation, controller/API duplication, Blade template updates for inheritance safety.
- EXCLUDE: Beta UI routes/views, `main/resources/views/frontend/trading-v1/**`, full theme packaging overhaul.
