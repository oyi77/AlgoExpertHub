# Tasks: Implement Landing Page Switcher

## Phase 1: Infrastructure

- [ ] Add `landing_page` column to `configurations` table [ ]
    -   `php artisan make:migration add_landing_page_to_configurations_table`
    -   `php artisan migrate`
- [ ] Update `Configuration` model with `landing_page` field [ ]

## Phase 2: Logic

- [ ] Implement `Helper::landingView()` to resolve landing page [ ]
- [ ] Update `FrontendController::index()` to use `Helper::landingView()` [ ]

## Phase 3: Admin UI

- [ ] Update `Backend/ConfigurationController.php` to handle saving `landing_page` [ ]
- [ ] Update Theme Settings view (`resources/views/backend/setting/theme.blade.php`) with landing page selector [ ]

## Phase 4: Bot Sales Landing Page

- [ ] Create directory structure `resources/views/frontend/landings/bot-sales/` [ ]
- [ ] Scaffold `layout/master.blade.php` for the new landing page [ ]
- [ ] Implement `index.blade.php` with sections [ ]
- [ ] Add CSS/JS assets in `public/asset/frontend/landings/bot-sales/` [ ]

## Phase 5: Verification

- [ ] Verify landing page switching from admin panel [ ]
- [ ] Verify Bot Sales landing page responsiveness and CTAs [ ]
- [ ] Ensure NO regressions in existing themes and dashboard [ ]
