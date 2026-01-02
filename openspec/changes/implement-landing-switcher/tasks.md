# Tasks: Implement Landing Page Switcher

## Phase 1: Infrastructure

- [x] Add `landing_page` column to `configurations` table [x]
    -   `php artisan make:migration add_landing_page_to_configurations_table` ✅
    -   `php artisan migrate` ✅
- [x] Update `Configuration` model with `landing_page` field [x]

## Phase 2: Logic

- [x] Implement `Helper::landingView()` to resolve landing page [x]
- [x] Update `FrontendController::index()` to use `Helper::landingView()` [x]

## Phase 3: Admin UI

- [x] Update `Backend/ConfigurationController.php` to handle saving `landing_page` [x]
- [x] Update Theme Settings view (`resources/views/backend/setting/theme.blade.php`) with landing page selector [x]

## Phase 4: Bot Sales Landing Page

- [x] Create directory structure `resources/views/frontend/landings/bot-sales/` [x]
- [x] Scaffold `layout/master.blade.php` for the new landing page [x]
- [x] Implement `index.blade.php` with sections [x]
- [x] Add CSS/JS assets in `public/asset/frontend/landings/bot-sales/` [x]

## Phase 5: Verification

- [ ] Verify landing page switching from admin panel [ ]
- [ ] Verify Bot Sales landing page responsiveness and CTAs [ ]
- [ ] Ensure NO regressions in existing themes and dashboard [ ]
