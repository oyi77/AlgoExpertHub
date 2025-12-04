# Technical Plan: User Menu Reorganization & New Trading Views

**Feature:** `menu-reorganization`  
**Scope:** User sidebar/menu reorganization, new `External Signal` & `Trading Overview` pages, light onboarding  
**Constraints:**  
- Do **not** rename or remove existing routes/controllers/services.  
- Respect all existing permissions.  
- Onboarding must not hard-block Dashboard or Support Ticket.

---

## 1. High-Level Architecture

### 1.1 Layers Affected
- **Views (Blade):**
  - User sidebar (`user_sidebar.blade.php`) across themes (default, dark, light, materialize, blue, premium).
  - New user pages:
    - `External Signal` (multi-tab wrapper).
    - `Trading Overview` (grid overview).
  - Dashboard widget for onboarding.
- **Routes:**
  - Add 2 new named routes:
    - `user.external-signals.index`
    - `user.trading.overview`
  - Keep all existing user routes intact.
- **Services/Helpers (optional, thin):**
  - Small helper for building user menu groups (to keep sidebar Blade cleaner).
  - Small onboarding helper/service to query checklist progress.

### 1.2 Data Sources Reused
- **External Signal:**
  - Existing controllers & routes for:
    - Signal Sources (`user.signal-sources.index`).
    - Channel Forwarding (`user.channel-forwarding.index`).
    - Pattern Templates (admin/user route(s) – to be wired in read-only mode).
- **Trading Overview:**
  - Execution Engine Addon models:
    - `ExecutionConnection` (user-level).
    - Possibly `ExecutionPosition` / `ExecutionAnalytic` for P/L snippets.
  - Copy-Trading Addon models:
    - User subscriptions / followed traders.
  - Any existing “bot”/AI model usage, if cleanly queryable.
- **Onboarding:**
  - Existing user state:
    - Profile completeness (simple checks on `users` table).
    - Plan subscription (`PlanSubscription` current plan).
    - Execution connections count.
    - External sources count (signal sources, forwarding rules).
    - Presets count.

---

## 2. Menu Architecture (User)

### 2.1 New Logical Groups

Implement grouping purely in Blade (no route renames), respecting:
- `AddonRegistry::active(...)` & `moduleEnabled(..., 'user_ui')`.
- Existing permissions and route-availability checks (`Route::has()`).

Groups:
1. **USER SETUPS**
2. **TRADING**
3. **REPORTS**
4. **MARKETPLACES**
5. **ACCOUNT**

### 2.2 Mapping Existing Menu Items → New Groups

**USER SETUPS**
- `External Signal` (new):
  - Wrap:
    - `user.signal-sources.index`
    - `user.channel-forwarding.index`
    - Pattern templates route (TBD – probably under addon).
- `Trade Accounts / My Connections`:
  - `user.execution-connections.index` (if route exists & addon enabled).
- `Trading Presets`:
  - `user.trading-presets.index` (My Presets).
- `Filter Strategies`:
  - `user.filter-strategies.index` (My Strategies).
- `AI Model Profiles`:
  - `user.ai-model-profiles.index` (My Profiles).

**TRADING**
- `Trading Overview` (new):
  - `user.trading.overview`.
- Internally links to:
  - Execution connections detail/edit routes.
  - Copy-trading settings/browse/history routes.

**REPORTS**
- Aggregate:
  - `user.execution-analytics.index` (if exists).
  - `user.srm.dashboard` / `user.srm.insights.index` / `user.srm.adjustments.index` (if enabled).
  - Logs:
    - `user.deposit.log`
    - `user.withdraw.all`
    - `user.invest.log`
    - `user.transaction.log`
    - `user.transfer_money.log`
    - `user.receive_money.log`
    - `user.commision`
    - `user.subscription`
  - Execution/position history routes (from Execution Engine addon) if present.

**MARKETPLACES**
- Preset Marketplace:
  - `user.trading-presets.marketplace`.
- Filter Strategy Marketplace:
  - `user.filter-strategies.marketplace`.
- AI Model Marketplace:
  - Any existing user-facing AI model marketplace route (from OpenRouter/AI addons).
- Copy-Trading Traders / Signal Marketplace:
  - `user.copy-trading.traders.index` (and similar).

**ACCOUNT**
- `user.plans` (Plans & subscriptions).
- `user.refferalLog` (Referral Program).
- `user.profile` (Profile Settings).
- `user.ticket.index` (Support Tickets).
- Notifications:
  - Existing notification list route (if any) or add a minimal list view that reads from `notifications` table filtered by user.
- `user.logout` remains in this group or separated at bottom.

### 2.3 Sidebar Rendering Logic

- Build a **computed array** `$menuGroups` in the Blade (or via small helper) with:
  - `label`, `icon`, `items[]`.
  - Each item contains:
    - `route`, `label`, optional `children` for submenus.
    - `visible` boolean based on:
      - `Route::has()`.
      - Addon module enabled.
      - (Optionally) minimum onboarding stage (only for “soft” suggestions, not hard block).
- Filter out:
  - Items where `visible === false`.
  - Groups where all items are filtered out.
- Render:
  - Section header (group label + icon).
  - Simple `<li>` for items without children.
  - `has_submenu` structure for nested lists.
- Mirror structure into **mobile bottom menu** but with fewer entries (e.g. Dashboard, Wallet core actions, and a “Menu” toggle).

---

## 3. New Views & Routes

### 3.1 External Signal Page

**Route (web.php / user routes):**
- `GET /user/external-signals` → `User\ExternalSignalController@index`
  - Name: `user.external-signals.index`.

**Controller:**
- New controller `App\Http\Controllers\User\ExternalSignalController`:
  - Thin wrapper:
    - Resolve data required for tabs:
      - Option 1: Use existing services/controllers to fetch data via internal service calls.
      - Option 2: Minimal data for tabs and let included partials handle querying like before.
  - Returns view `frontend.{theme}.user.external_signals`.

**View Structure (`resources/views/frontend/{theme}/user/external_signals.blade.php`):**
- Extends existing user layout.
- Contains:
  - Tabs (Bootstrap nav-pills):
    - Signal Sources
    - Channel Forwarding
    - Pattern Templates
  - Content area:
    - Either:
      - `@include` partials containing the main table/forms of each old page.
      - Or use AJAX to request each existing route and inject HTML.
- Refactor existing separate views:
  - Extract **core content blocks** to partials:
    - e.g. `frontend/{theme}/user/signal_sources/_content.blade.php`.
  - Old views include those partials, new multi-tab view also includes them.
  - This way controller logic and forms stay the same.

### 3.2 Trading Overview Page

**Route:**
- `GET /user/trading-overview` → `User\TradingOverviewController@index`
  - Name: `user.trading.overview`.

**Controller:**
- New `App\Http\Controllers\User\TradingOverviewController`:
  - Dependencies:
    - Execution Engine addon models/services for connections & analytics.
    - Copy-Trading addon models for subscriptions.
  - `index()`:
    - Query:
      - Active execution connections for `auth()->id()`.
      - Active/past copy-trading subscriptions.
      - Simple P/L snapshot (from positions/logs or analytics) where cheap.
    - Map to simple DTO/array per card:
      - `id`, `type`, `name`, `status`, `broker`, `preset_name`, `pl_today`, `pl_week`, `details_route`, `toggle_route`.
    - Pass to view.

**View (`frontend/{theme}/user/trading_overview.blade.php`):**
- Layout:
  - Title, filters (optional).
  - Responsive grid:
    - Desktop: 3–4 cards per row.
    - Tablet: 2 cards.
    - Mobile: 1 card.
- Each card:
  - Shows core info & status.
  - Buttons:
    - `Manage` → link to existing details route.
    - `Start/Stop`:
      - Link or small form posting to existing toggle routes.
      - **No new trading logic**; just UI triggers existing flows.

---

## 4. Onboarding Architecture

### 4.1 Onboarding Checklist

**Checklist items:**
1. Complete Profile.
2. Choose a Plan.
3. Connect Trade Account (My Connections).
4. Add External Signal (at least one Signal Source or Channel Forwarding).
5. Create a Trading Preset.

**Implementation:**
- Create `App\Services\UserOnboardingService`:
  - `getChecklist(User $user): array` → items with `id`, `label`, `completed`.
  - Each item is computed from existing data:
    - Profile: required fields not null.
    - Plan: `currentplan()` not empty.
    - Connections: `ExecutionConnection::where('user_id')` count > 0.
    - External Signal: channel sources/forwarding exists.
    - Presets: `TradingPreset` count > 0 for user.
- No new DB tables required initially (purely computed).

**UI:**
- Dashboard view: include partial `user/_onboarding_widget.blade.php`.
- Show progress bar + list of steps.
- Add links to relevant pages for each step.

### 4.2 Tooltips / Info Banners

**Mechanism:**
- Use a lightweight approach:
  - Blade checks small flags stored in user `meta` column or a simple `user_onboarding_preferences` table (optional, small).
  - Or store a JSON `onboarding_dismissed` on users table if already present.
- For each key page (`USER SETUPS`, `TRADING`, `MARKETPLACES`):
  - At top of content, show dismissible alert (`@if (!dismissed)`).
  - Dismiss via AJAX to simple route that sets the flag.

**Guardrail:**
- Do not use middleware to block access.
- Only show hints / recommendations.

---

## 5. Permissions & Visibility Rules

- **Group-level visibility:**
  - Compute `visible` if **any** child item is visible.
  - Child visible if:
    - Route exists (`Route::has()`).
    - Related addon module is active & `moduleEnabled(..., 'user_ui')`.
    - User meets permission checks from existing controllers/middleware.
- **No changes** to existing permission middleware stacks.
- **External Signal group:**
  - Shown only when at least one of the underlying features (sources/forwarding/templates) is available.

---

## 6. Testing Strategy

### 6.1 Manual / Feature Testing

Scenarios:
1. User without any addons:
   - Sidebar should degrade to: Dashboard, Plans, Wallet, Account, Support.
   - No empty groups visible.
2. User with all addons enabled:
   - All groups visible.
   - External Signal tabs load all three contents correctly.
   - Trading Overview cards show data without errors even if some sections are empty.
3. New user (no setups):
   - Onboarding widget shows all steps unchecked.
   - Trading Overview shows informative empty state.
4. Partially configured user:
   - Checklist reflects real state.
   - Only relevant groups visible based on permissions & addons.

### 6.2 Regression Checks

- All original routes:
  - Still respond and work when accessed directly by URL.
- Existing pages:
  - Signal Sources, Channel Forwarding, Trading Presets, etc. still function.
- No 500 errors in:
  - Sidebar rendering when some addons disabled.
  - Trading Overview when data missing.

---

## 7. Rollout Strategy

1. Implement new views & routes behind feature flag/config (optional).
2. Update user sidebar to **parallel-render** old & new structure in a test environment.
3. After verification:
   - Switch sidebar to only new grouped structure.
   - Keep old routes callable but unlinked.
4. Monitor logs & user feedback.


