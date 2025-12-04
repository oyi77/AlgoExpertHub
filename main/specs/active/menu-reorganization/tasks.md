# Tasks: User Menu Reorganization & New Trading Views

**Feature:** `menu-reorganization`  
**Goal:** Reorganize user menu, add External Signal & Trading Overview pages, and light onboarding.

---

## 1. Planning & Validation

### T1.1 – Review Existing Routes & Permissions
- **Description:** Catalogue all existing user routes related to signals, trading, reports, marketplaces, and account to ensure correct mapping into new groups.
- **Steps:**
  - Scan `routes/web.php` (user section) and addon `routes/user.php` files.
  - List routes:
    - Signal Sources, Channel Forwarding, Pattern Templates.
    - Execution Engine (connections, analytics, positions).
    - Trading Presets, Filter Strategies, AI Model Profiles.
    - Copy Trading, SRM, marketplaces, logs, account.
  - Note any routes that are **admin-only** to avoid mapping into user menu.
- **Estimate:** 0.5 day  
- **Dependencies:** None.  
- **Acceptance Criteria:**
  - Documented map: route name → intended group (`USER SETUPS`, `TRADING`, `REPORTS`, `MARKETPLACES`, `ACCOUNT`).

---

## 2. External Signal Page

### T2.1 – Create ExternalSignalController & Route
- **Description:** Add new controller and route for `External Signal` wrapper page without changing existing controllers.
- **Steps:**
  - Create `App\Http\Controllers\User\ExternalSignalController` with `index()` method.
  - Register route `user.external-signals.index` in user routes file.
  - Ensure route uses same middleware stack as other authenticated user pages.
- **Estimate:** 0.5 day  
- **Dependencies:** T1.1.  
- **Acceptance Criteria:**
  - Hitting `/user/external-signals` renders a basic page (empty shell) with user layout.

### T2.2 – Extract Existing Content into Partials
- **Description:** Make existing Signal Sources, Channel Forwarding, and Pattern Templates views reusable.
- **Steps:**
  - Identify current Blade views used for:
    - Signal Sources.
    - Channel Forwarding.
    - Pattern Templates (user-facing).
  - Extract the core **content section** of each into partials (e.g. `_signal_sources_content.blade.php`).
  - Update original pages to `@include` the partials so behavior remains unchanged.
- **Estimate:** 1 day  
- **Dependencies:** T2.1.  
- **Acceptance Criteria:**
  - Old routes still render exactly as before using partials.
  - No duplicated logic between old views and new partials.

### T2.3 – Build External Signal Multi-Tab View
- **Description:** Implement multi-tab UI that composes three existing content blocks.
- **Steps:**
  - Create `frontend/{theme}/user/external_signals.blade.php` with:
    - Tabs: “Signal Sources”, “Channel Forwarding”, “Pattern Templates”.
    - Default active tab = Signal Sources.
  - Within each tab pane, `@include` the corresponding partial.
  - Ensure forms, tables, and actions work as before (using same routes).
  - Test across at least one theme; replicate to other themes if layout differs.
- **Estimate:** 1 day  
- **Dependencies:** T2.2.  
- **Acceptance Criteria:**
  - All three tabs render and function identically to their standalone pages.
  - No console or server errors when switching tabs or submitting forms.

---

## 3. Trading Overview Page

### T3.1 – Design TradingOverview Data Model (Read-Only)
- **Description:** Decide which models and fields will be used to populate Trading Overview cards.
- **Steps:**
  - Review Execution Engine addon models:
    - `ExecutionConnection`, `ExecutionPosition`, `ExecutionAnalytic`.
  - Review Copy-Trading addon models:
    - User subscriptions, followed traders.
  - Define DTO/array structure for card data:
    - `id`, `type`, `name`, `status`, `broker`, `preset_name`, `pl_today`, `pl_week`, `details_route`, `toggle_route`.
- **Estimate:** 0.5 day  
- **Dependencies:** T1.1.  
- **Acceptance Criteria:**
  - Documented mapping: which models & fields populate each card property.

### T3.2 – Implement TradingOverviewController & Route
- **Description:** Create controller to gather trading setup data and expose to view.
- **Steps:**
  - Add `App\Http\Controllers\User\TradingOverviewController@index`.
  - Query:
    - Execution connections for the user (active + recently used).
    - Copy-trading subscriptions (active + recent).
  - Map to card DTO structure.
  - Register route `user.trading.overview` in user routes, with same middleware as other user pages.
- **Estimate:** 1 day  
- **Dependencies:** T3.1.  
- **Acceptance Criteria:**
  - Controller returns structured card data with no N+1 queries (use eager loading).
  - Basic test view can loop cards without errors even if data is empty.

### T3.3 – Build Trading Overview Grid View
- **Description:** Implement responsive card grid UI that uses TradingOverviewController data.
- **Steps:**
  - Create `frontend/{theme}/user/trading_overview.blade.php`.
  - Layout:
    - Page title + optional filters.
    - Responsive grid (Bootstrap): 3–4 cards/row on desktop, 1–2 on mobile.
  - Card contents:
    - Show properties from DTO (name, type, status, broker, preset, P/L).
    - Buttons:
      - `Manage` → `route($card['details_route'], ...)`.
      - `Start/Stop`:
        - Either link to existing toggle routes or small `POST` form using existing routes.
  - Handle empty state (no setups) gracefully with CTA links to User Setups / Marketplaces.
- **Estimate:** 1.5 days  
- **Dependencies:** T3.2.  
- **Acceptance Criteria:**
  - Page renders correctly across screen sizes.
  - Buttons navigate to existing detail pages and do not 404.
  - No new business logic added; all actions reuse existing routes.

---

## 4. User Sidebar Reorganization

### T4.1 – Implement Menu Group Builder Logic
- **Description:** Centralize group & item visibility logic for user sidebar.
- **Steps:**
  - In `user_sidebar.blade.php` (or helper), construct `$menuGroups`:
    - Groups: USER SETUPS, TRADING, REPORTS, MARKETPLACES, ACCOUNT.
    - Items with `label`, `icon`, `route`, optional `children`.
  - For each item, compute `visible` based on:
    - `Route::has()`.
    - Addon module enabled (`AddonRegistry` checks).
    - Any other existing guards (e.g. `auth()->user()->currentplan()` where needed).
  - Filter groups where no items are visible.
- **Estimate:** 1 day  
- **Dependencies:** T1.1, T2.1, T3.2.  
- **Acceptance Criteria:**
  - `$menuGroups` correctly describes all intended items.
  - Disabled addons & missing routes result in groups disappearing cleanly (no errors).

### T4.2 – Update User Sidebar Blade to New Structure
- **Description:** Replace the flat/legacy sidebar list with loop over `$menuGroups`.
- **Steps:**
  - Refactor `frontend/default/layout/user_sidebar.blade.php`:
    - Use loops to render group labels + items.
    - Preserve existing active-menu logic (`Config::singleMenu`, `Config::activeMenu`).
  - Mirror logic or `@include` for other themes (dark, light, materialize, blue, premium) to avoid code drift.
  - Keep Dashboard, Support Ticket, and Logout always accessible items (independent of onboarding).
- **Estimate:** 1.5 days  
- **Dependencies:** T4.1, T2.3, T3.3.  
- **Acceptance Criteria:**
  - UI shows 5 top-level groups max.
  - No group is rendered empty.
  - Existing permissions/middleware still control access to underlying pages.

### T4.3 – Adjust Mobile Bottom Menu
- **Description:** Align mobile bottom menu items with new structure.
- **Steps:**
  - Update bottom menu in `user_sidebar.blade.php` (or separate partial):
    - Ensure core quick actions: Dashboard, Deposit, Transfer, Withdraw, Menu.
  - Confirm navigation to new grouped sidebar works correctly.
- **Estimate:** 0.5 day  
- **Dependencies:** T4.2.  
- **Acceptance Criteria:**
  - Mobile experience remains intuitive with no broken links.

---

## 5. Reports & Marketplaces Grouping

### T5.1 – Map Existing Report Routes into REPORTS Group
- **Description:** Centralize all log/analytics/insight pages under REPORTS.
- **Steps:**
  - From T1.1 mapping, assign:
    - Logs (deposit, withdraw, transaction, etc.).
    - Execution/position history.
    - SRM dashboards/insights.
    - Trading Analytics.
  - Ensure only routes that exist & are user-facing are included.
- **Estimate:** 0.5 day  
- **Dependencies:** T1.1.  
- **Acceptance Criteria:**
  - REPORTS group in menu references all report-like routes and nothing else.

### T5.2 – Map Existing Marketplace Routes into MARKETPLACES Group
- **Description:** Centralize all “marketplace” experiences.
- **Steps:**
  - Add menu items and/or single marketplace index:
    - Preset Marketplace.
    - Filter Strategy Marketplace.
    - AI Model Marketplace.
    - Copy Trading / Traders Marketplace.
  - Optionally create a lightweight `Marketplaces` index page that links into each marketplace.
- **Estimate:** 1 day  
- **Dependencies:** T1.1, T4.1.  
- **Acceptance Criteria:**
  - All “browse/buy/subscribe” experiences are under MARKETPLACES.
  - No duplication with USER SETUPS or TRADING.

---

## 6. Account & Notifications

### T6.1 – Consolidate Account-Related Menus
- **Description:** Group all account-related routes in ACCOUNT.
- **Steps:**
  - Map:
    - Plans & Subscriptions.
    - Referral Log.
    - Profile.
    - Support Tickets.
    - Logout.
  - Ensure they always remain visible (subject to existing guards), independent of onboarding.
- **Estimate:** 0.5 day  
- **Dependencies:** T4.1, T4.2.  
- **Acceptance Criteria:**
  - ACCOUNT section clearly holds all user-account-related options.

### T6.2 – Wire Notifications Menu
- **Description:** Provide a menu entry to existing notification list/settings if available.
- **Steps:**
  - Locate any existing route for user notifications.
  - If none:
    - Create a minimal read-only list page that shows `auth()->user()->notifications`.
  - Add menu item under ACCOUNT.
- **Estimate:** 1 day  
- **Dependencies:** T1.1.  
- **Acceptance Criteria:**
  - Users can access their notifications from ACCOUNT.

---

## 7. Onboarding Implementation

### T7.1 – Implement UserOnboardingService
- **Description:** Service to compute onboarding checklist state without new DB tables initially.
- **Steps:**
  - Create `App\Services\UserOnboardingService` with:
    - `getChecklist(User $user): array`.
  - Implement checks using existing models:
    - Profile completeness.
    - Current plan.
    - Connections count.
    - External signals count.
    - Presets count.
- **Estimate:** 1 day  
- **Dependencies:** T1.1.  
- **Acceptance Criteria:**
  - Unit tests or tinker checks show correct completion flags for several user scenarios.

### T7.2 – Add Onboarding Widget to Dashboard
- **Description:** Display checklist with progress on user dashboard.
- **Steps:**
  - Add partial view `user/_onboarding_widget.blade.php`.
  - Inject `OnboardingService` output into dashboard controller and pass to view.
  - Render checklist with progress bar + links to relevant pages.
- **Estimate:** 1 day  
- **Dependencies:** T7.1.  
- **Acceptance Criteria:**
  - Widget appears on dashboard for all users.
  - Checklist correctly reflects user state.

### T7.3 – Implement Dismissible Info Banners / Tooltips
- **Description:** Provide contextual help on key pages without blocking usage.
- **Steps:**
  - Add simple storage for user’s dismissed banners:
    - Either use a JSON column on users or a minimalist `user_onboarding_preferences` table.
  - For pages:
    - USER SETUPS, TRADING, MARKETPLACES.
  - Render dismissible alert/tooltip if not dismissed.
  - AJAX/POST route to mark banner as dismissed.
- **Estimate:** 1.5 days  
- **Dependencies:** T7.1.  
- **Acceptance Criteria:**
  - Banners appear only until dismissed.
  - Dashboard & Support Ticket never blocked by onboarding state.

---

## 8. Testing & QA

### T8.1 – Scenario Testing Without Addons
- **Description:** Ensure platform behaves correctly with core-only features.
- **Steps:**
  - Disable all trading-related addons in AddonRegistry.
  - Login as user with basic permissions.
  - Verify:
    - No empty groups.
    - Dashboard, Plans, Wallet, Account, Support function correctly.
- **Estimate:** 0.5 day  
- **Dependencies:** All core tasks (T2–T7).  
- **Acceptance Criteria:**
  - No broken links or 500 errors in navigation.

### T8.2 – Scenario Testing With All Addons Enabled
- **Description:** Validate full experience.
- **Steps:**
  - Enable trading, SRM, AI, copy trading, multi-channel, etc.
  - Use user with full access.
  - Verify:
    - External Signal tabs.
    - Trading Overview cards from all data sources.
    - Reports & Marketplaces menus.
    - Onboarding widget & banners.
- **Estimate:** 1 day  
- **Dependencies:** All implementation tasks.  
- **Acceptance Criteria:**
  - All features reachable through new menu structure.
  - No regressions in existing flows.

### T8.3 – Cross-Theme Visual Check
- **Description:** Ensure sidebar & new pages render correctly in all themes.
- **Steps:**
  - Switch between default, dark, light, materialize, blue, premium.
  - Check:
    - Sidebar layout.
    - External Signal tabs.
    - Trading Overview grid.
    - Onboarding widget.
- **Estimate:** 0.5 day  
- **Dependencies:** T4.2, T2.3, T3.3, T7.2.  
- **Acceptance Criteria:**
  - No broken layout or unreadable text in any theme.


