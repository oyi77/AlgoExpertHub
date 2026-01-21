# DEX Analytics Addon - Expanded Task List with UI

**Created**: 2026-01-21
**Purpose**: Expand the original 31-task plan to 57 tasks with comprehensive UI implementation

---

## Task Expansion Summary

| Original Phase | Original Tasks | New Tasks Added | Total Tasks | Focus |
|----------------|----------------|-----------------|-------------|-------|
| Phase 1: Skeleton | 1-5 (5 tasks) | None | 5 | Addon structure |
| Phase 2: API Clients | 6-10 (5 tasks) | None | 5 | Platform integrations |
| Phase 3: Normalization | 11-15 (5 tasks) | None | 5 | Data processing |
| Phase 4: Analytics | 16-18e (8 tasks) | None | 8 | Analytics engine |
| **Phase 5: Admin UI** | **19-21 (3 tasks)** | **+12 tasks** | **15** | **Admin interface** |
| **Phase 6: User UI** | **None** | **+8 tasks** | **8** | **User interface** |
| Phase 7: Jobs | 22-25 (4 tasks) | None | 4 | Background processing |
| Phase 8: Testing | 26-28 (3 tasks) | +4 tasks | 7 | Quality assurance |

**Total**: 31 original tasks → **57 tasks** (26 new UI/testing tasks added)

---

## Expanded Task List

### Phase 1: Addon Skeleton & Core Integration (Tasks 1-5)
*No changes - keep original 5 tasks*

### Phase 2: API Clients (Tasks 6-10)
*No changes - keep original 5 tasks*

### Phase 3: Normalization & Storage (Tasks 11-15)
*No changes - keep original 5 tasks*

### Phase 4: Analytics Engine (Tasks 16-18e)
*No changes - keep original 8 tasks*

---

### Phase 5: Admin UI (Tasks 19-33) - EXPANDED

#### Task 19: Create admin routes structure
*Original task - enhanced*

**What to do**:
- Create `routes/admin.php` in addon directory
- Define route groups with proper middleware
- Implement RESTful routing for all admin pages
- Apply middleware: `['web', 'admin', 'demo', 'permission:manage-dex-analytics,admin']`

**Routes to create**:
```php
// Dashboard
Route::get('/', [DexAnalyticsController::class, 'dashboard'])->name('dashboard');

// Watchlist
Route::resource('watchlist', WatchlistController::class);
Route::post('watchlist/import', [WatchlistController::class, 'import'])->name('watchlist.import');
Route::get('watchlist/export', [WatchlistController::class, 'export'])->name('watchlist.export');

// Analytics
Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/', [AnalyticsController::class, 'index'])->name('index');
    Route::get('/trader/{wallet}', [AnalyticsController::class, 'trader'])->name('trader');
    Route::get('/performance', [AnalyticsController::class, 'performance'])->name('performance');
    Route::get('/pnl', [AnalyticsController::class, 'pnl'])->name('pnl');
    Route::get('/positions', [AnalyticsController::class, 'positions'])->name('positions');
    Route::get('/funding', [AnalyticsController::class, 'funding'])->name('funding');
    Route::get('/liquidations', [AnalyticsController::class, 'liquidations'])->name('liquidations');
});

// Leaderboards
Route::prefix('leaderboards')->name('leaderboards.')->group(function () {
    Route::get('/', [LeaderboardController::class, 'index'])->name('index');
    Route::get('/top-traders', [LeaderboardController::class, 'topTraders'])->name('top-traders');
    Route::get('/smart-money', [LeaderboardController::class, 'smartMoney'])->name('smart-money');
    Route::get('/copy-suitable', [LeaderboardController::class, 'copySuitable'])->name('copy-suitable');
});

// AI Insights
Route::prefix('ai-insights')->name('ai-insights.')->group(function () {
    Route::get('/', [AiInsightsController::class, 'index'])->name('index');
    Route::post('/analyze', [AiInsightsController::class, 'analyze'])->name('analyze');
    Route::get('/clustering', [AiInsightsController::class, 'clustering'])->name('clustering');
    Route::get('/crowded-trades', [AiInsightsController::class, 'crowdedTrades'])->name('crowded-trades');
    Route::get('/regime', [AiInsightsController::class, 'regime'])->name('regime');
});

// Settings
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::post('/', [SettingsController::class, 'update'])->name('update');
    Route::post('/test-platform', [SettingsController::class, 'testPlatform'])->name('test-platform');
});
```

**Acceptance Criteria**:
- [ ] All routes registered in `routes/admin.php`
- [ ] Middleware applied correctly
- [ ] Route names follow convention: `admin.dex-analytics.{resource}.{action}`
- [ ] `php artisan route:list --name=dex-analytics` shows all routes

---

#### Task 20: Create admin controllers
*Original task - enhanced*

**What to do**:
- Create controller directory: `App/Http/Controllers/Backend/`
- Create 6 controllers:
  1. `DexAnalyticsController.php` (Dashboard)
  2. `WatchlistController.php` (Watchlist CRUD)
  3. `AnalyticsController.php` (Analytics pages)
  4. `LeaderboardController.php` (Leaderboard pages)
  5. `AiInsightsController.php` (AI features)
  6. `SettingsController.php` (Configuration)

**Controller Pattern**:
```php
namespace Addons\DexAnalyticsAddon\App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\DexAnalyticsAddon\App\Services\{ServiceName};

class DexAnalyticsController extends Controller
{
    protected $analyticsService;
    
    public function __construct({ServiceName} $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }
    
    public function dashboard()
    {
        $stats = $this->analyticsService->getDashboardStats();
        $recentActivity = $this->analyticsService->getRecentActivity();
        $platformHealth = $this->analyticsService->getPlatformHealth();
        
        return view('dex-analytics::backend.dashboard', compact('stats', 'recentActivity', 'platformHealth'));
    }
}
```

**Acceptance Criteria**:
- [ ] All 6 controllers created
- [ ] Dependency injection used for services
- [ ] Return format: views with compact data
- [ ] Error handling implemented

---

#### Task 21: Create admin dashboard view

**What to do**:
- Create `resources/views/backend/dashboard.blade.php`
- Implement stats cards (4 cards)
- Create recent activity table (DataTable)
- Add platform health indicators

**View Structure**:
```blade
@extends('backend.layout.master')

@section('title', 'DEX Analytics Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Tracked Traders</h5>
                    <h2>{{ $stats['total_traders'] }}</h2>
                </div>
            </div>
        </div>
        <!-- More cards... -->
    </div>
    
    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h4>Recent Activity</h4>
        </div>
        <div class="card-body">
            <table id="activity-table" class="table table-striped">
                <!-- Table content -->
            </table>
        </div>
    </div>
    
    <!-- Platform Health -->
    <div class="card mt-4">
        <div class="card-header">
            <h4>Platform Health</h4>
        </div>
        <div class="card-body">
            <!-- Health indicators -->
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#activity-table').DataTable({
        order: [[4, 'desc']], // Sort by timestamp
        pageLength: 25
    });
});
</script>
@endsection
```

**Acceptance Criteria**:
- [ ] Dashboard view created
- [ ] Stats cards display correctly
- [ ] DataTable initialized
- [ ] Platform health indicators functional
- [ ] Responsive design

---

#### Task 22: Create watchlist views

**What to do**:
- Create `resources/views/backend/watchlist/` directory
- Create 3 views:
  1. `index.blade.php` (List with DataTable)
  2. `create.blade.php` (Add trader form)
  3. `edit.blade.php` (Edit trader form)

**Index View Features**:
- DataTable with columns: Wallet Address, Platform, Added Date, Status, Actions
- Add Trader button
- Import/Export buttons
- Bulk actions (Remove Selected)

**Form Views Features**:
- Wallet Address input (with validation)
- Platform dropdown (GMX, Hyperliquid, Aster, Lighter, dYdX v4)
- Notes textarea
- Submit/Cancel buttons

**Acceptance Criteria**:
- [ ] All 3 views created
- [ ] DataTable functional with sorting/filtering
- [ ] Forms validate input
- [ ] Import/Export buttons functional
- [ ] CRUD operations work

---

#### Task 23: Create analytics views

**What to do**:
- Create `resources/views/backend/analytics/` directory
- Create main view: `index.blade.php` (with tabs)
- Create 5 tab partials:
  1. `_performance.blade.php`
  2. `_pnl.blade.php`
  3. `_positions.blade.php`
  4. `_funding.blade.php`
  5. `_liquidations.blade.php`

**Tab Structure**:
```blade
<!-- index.blade.php -->
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#performance">Performance</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#pnl">PnL</a>
            </li>
            <!-- More tabs... -->
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div id="performance" class="tab-pane fade show active">
                @include('dex-analytics::backend.analytics._performance')
            </div>
            <div id="pnl" class="tab-pane fade">
                @include('dex-analytics::backend.analytics._pnl')
            </div>
            <!-- More tab panes... -->
        </div>
    </div>
</div>
```

**Performance Tab Features**:
- Trader selector dropdown
- Time range selector (7D, 30D, 90D, 180D, All)
- Metrics cards (8 metrics)
- Chart.js line chart (Cumulative PnL)

**PnL Tab Features**:
- PnL table (DataTable)
- Filters: Platform, Symbol, Side, Date Range
- PnL heatmap (calendar visualization)

**Positions Tab Features**:
- Open positions table
- Closed positions table
- Toggle between open/closed

**Funding Tab Features**:
- Funding payments table
- Funding cost chart (Bar chart)

**Liquidations Tab Features**:
- Liquidation events table
- Liquidation analysis summary

**Acceptance Criteria**:
- [ ] All views created
- [ ] Tabs functional
- [ ] Charts render correctly (Chart.js)
- [ ] DataTables initialized
- [ ] Filters work
- [ ] Data loads dynamically

---

#### Task 24: Create leaderboard views

**What to do**:
- Create `resources/views/backend/leaderboards/` directory
- Create main view: `index.blade.php` (with tabs)
- Create 3 tab partials:
  1. `_top-traders.blade.php`
  2. `_smart-money.blade.php`
  3. `_copy-suitable.blade.php`

**Top Traders Tab Features**:
- Leaderboard table with ranking
- Columns: Rank, Wallet, Platform, Total PnL, Win Rate, Profit Factor, Confidence Score
- Filters: Platform, Time Range
- Actions: View Analytics, Add to Watchlist

**Smart Money Tab Features**:
- Smart money table
- Labels with emoji: 🧠 Smart Money, 🐋 Whale, 💎 Diamond Hands, 📄 Paper Hands, ⚡ HFT/Scalper
- Confidence percentage badges
- Metrics display

**Copy-Suitable Tab Features**:
- Copy-suitability table
- Copy Score (0-100) with color coding
- Trade frequency, Avg position size, Max drawdown columns
- Replicability indicator

**Acceptance Criteria**:
- [ ] All views created
- [ ] Tabs functional
- [ ] Rankings display correctly
- [ ] Labels/badges render
- [ ] Filters work
- [ ] Actions functional

---

#### Task 25: Create AI insights views

**What to do**:
- Create `resources/views/backend/ai-insights/` directory
- Create main view: `index.blade.php`
- Implement 3 sections:
  1. Behavior Clustering
  2. Crowded Trades Detection
  3. Regime Detection

**Behavior Clustering Section**:
- Cluster visualization (scatter plot or grouped cards)
- Cluster descriptions (AI-generated text)
- Traders per cluster count

**Crowded Trades Section**:
- Crowded trades table
- Columns: Symbol, Number of Traders, Total Position Size, Risk Level
- Alert badge if >50% traders in same position

**Regime Detection Section**:
- Current regime display (Trending/Ranging/Volatile)
- Regime-specific insights (AI-generated)
- Historical regime chart

**AI Configuration Section**:
- AI Connection selector (from AI Connection Addon)
- Model selector
- Analyze button

**Acceptance Criteria**:
- [ ] View created
- [ ] Clustering visualization works
- [ ] Crowded trades table functional
- [ ] Regime detection displays
- [ ] AI configuration form works
- [ ] Analyze button triggers AI analysis

---

#### Task 26: Create settings view

**What to do**:
- Create `resources/views/backend/settings/` directory
- Create main view: `index.blade.php`
- Implement 5 configuration sections:
  1. Platform Configuration
  2. Polling Configuration
  3. Data Retention
  4. AI Configuration
  5. Leaderboard Rules

**Platform Configuration Section**:
- Per-platform settings (5 platforms)
- Fields: API URL, Rate Limit, Timeout, Enabled toggle
- Test Connection button per platform

**Polling Configuration Section**:
- Polling interval input (default: 60s)
- Analytics refresh interval (default: 5 minutes)
- Retry logic settings

**Data Retention Section**:
- Raw data retention days (default: 90)
- Aggregated data retention (permanent)
- Cleanup schedule

**AI Configuration Section**:
- AI Connection selector
- AI Model dropdown
- AI Features checkboxes (Clustering, Crowded Trades, Regime Detection)

**Leaderboard Rules Section**:
- Minimum trades input
- Minimum volume input
- Confidence score threshold

**Acceptance Criteria**:
- [ ] View created
- [ ] All sections implemented
- [ ] Forms validate input
- [ ] Test buttons functional
- [ ] Settings save correctly
- [ ] Default values loaded

---

#### Task 27: Create admin layout and navigation

**What to do**:
- Update `main/resources/views/backend/layout/sidebar.blade.php`
- Add DEX Analytics menu item with submenu
- Create addon-specific layout if needed

**Menu Structure**:
```blade
@if(AddonRegistry::active('dex-analytics-addon') && AddonRegistry::moduleEnabled('dex-analytics-addon', 'admin_ui'))
<li class="nav-item has-treeview">
    <a href="#" class="nav-link">
        <i class="nav-icon fas fa-chart-line"></i>
        <p>
            DEX Analytics
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a href="{{ route('admin.dex-analytics.dashboard') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Dashboard</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.dex-analytics.watchlist.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Watchlist</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.dex-analytics.analytics.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Analytics</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.dex-analytics.leaderboards.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Leaderboards</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.dex-analytics.ai-insights.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>AI Insights</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.dex-analytics.settings.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Settings</p>
            </a>
        </li>
    </ul>
</li>
@endif
```

**Acceptance Criteria**:
- [ ] Menu item added to sidebar
- [ ] Submenu items functional
- [ ] Active state highlights correctly
- [ ] Permissions checked
- [ ] Module enablement checked

---

#### Task 28: Create admin assets (CSS/JS)

**What to do**:
- Create `resources/assets/backend/` directory in addon
- Create `css/dex-analytics.css` (custom styles)
- Create `js/dex-analytics.js` (custom scripts)
- Compile assets if using Laravel Mix

**CSS Features**:
- Custom card styles
- Badge colors for labels
- Chart container styles
- Responsive adjustments

**JS Features**:
- DataTable initialization helper
- Chart.js configuration helper
- AJAX request helpers
- Form validation helpers

**Acceptance Criteria**:
- [ ] CSS file created
- [ ] JS file created
- [ ] Assets compiled
- [ ] Assets loaded in views
- [ ] No console errors

---

### Phase 6: User UI (Tasks 29-36) - NEW

#### Task 29: Create user routes structure

**What to do**:
- Create `routes/user.php` in addon directory
- Define user-facing routes with proper middleware
- Apply middleware: `['web', 'auth', 'inactive', 'is_email_verified', '2fa', 'kyc']`

**Routes to create**:
```php
// Dashboard
Route::get('/', [User\DexAnalyticsController::class, 'dashboard'])->name('dashboard');

// Watchlist (view-only)
Route::get('/watchlist', [User\WatchlistController::class, 'index'])->name('watchlist.index');

// Analytics (filtered)
Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/', [User\AnalyticsController::class, 'index'])->name('index');
    Route::get('/trader/{wallet}', [User\AnalyticsController::class, 'trader'])->name('trader');
});

// Leaderboards (public)
Route::get('/leaderboards', [User\LeaderboardController::class, 'index'])->name('leaderboards.index');

// AI Insights (subscription-based)
Route::prefix('ai-insights')->name('ai-insights.')->middleware('subscription:premium')->group(function () {
    Route::get('/', [User\AiInsightsController::class, 'index'])->name('index');
});
```

**Acceptance Criteria**:
- [ ] User routes registered
- [ ] Middleware applied
- [ ] Route names follow convention: `user.dex-analytics.{resource}.{action}`
- [ ] Subscription checks implemented where needed

---

#### Task 30: Create user controllers

**What to do**:
- Create controller directory: `App/Http/Controllers/User/`
- Create 4 controllers:
  1. `DexAnalyticsController.php` (Dashboard)
  2. `WatchlistController.php` (View-only watchlist)
  3. `AnalyticsController.php` (Filtered analytics)
  4. `LeaderboardController.php` (Public leaderboards)
  5. `AiInsightsController.php` (Subscription-based AI)

**Controller Pattern** (with filtering):
```php
namespace Addons\DexAnalyticsAddon\App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Addons\DexAnalyticsAddon\App\Services\{ServiceName};

class DexAnalyticsController extends Controller
{
    protected $analyticsService;
    
    public function __construct({ServiceName} $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }
    
    public function dashboard()
    {
        $user = auth()->user();
        
        // Filter data based on user's subscription/permissions
        $stats = $this->analyticsService->getDashboardStats($user);
        $assignedTraders = $this->analyticsService->getAssignedTraders($user);
        
        return view('dex-analytics::user.dashboard', compact('stats', 'assignedTraders'));
    }
}
```

**Acceptance Criteria**:
- [ ] All 5 controllers created
- [ ] Data filtering implemented
- [ ] Subscription checks in place
- [ ] Error handling for unauthorized access

---

#### Task 31: Create user dashboard view

**What to do**:
- Create `resources/views/user/dashboard.blade.php`
- Implement filtered stats cards
- Create assigned traders overview table

**View Features**:
- Stats cards (filtered to user's assigned traders)
- Assigned traders table
- Quick links to analytics

**Acceptance Criteria**:
- [ ] View created
- [ ] Stats display correctly
- [ ] Only assigned traders shown
- [ ] Responsive design

---

#### Task 32: Create user watchlist view

**What to do**:
- Create `resources/views/user/watchlist/index.blade.php`
- Implement view-only watchlist table
- No CRUD operations (view-only)

**View Features**:
- Watchlist table (DataTable)
- Columns: Wallet Address, Platform, Status
- View Analytics action only (no edit/delete)

**Acceptance Criteria**:
- [ ] View created
- [ ] Table displays assigned traders only
- [ ] No edit/delete buttons
- [ ] View Analytics link works

---

#### Task 33: Create user analytics views

**What to do**:
- Create `resources/views/user/analytics/` directory
- Reuse admin analytics views structure
- Apply data filtering

**View Features**:
- Same tab structure as admin
- Data filtered to user's assigned traders
- Same charts and tables

**Acceptance Criteria**:
- [ ] Views created
- [ ] Data filtered correctly
- [ ] Charts render
- [ ] Tables functional

---

#### Task 34: Create user leaderboard views

**What to do**:
- Create `resources/views/user/leaderboards/index.blade.php`
- Display public leaderboards only

**View Features**:
- Top traders leaderboard (public)
- No smart money or copy-suitable tabs (admin-only)

**Acceptance Criteria**:
- [ ] View created
- [ ] Public leaderboard displays
- [ ] No admin-only data shown

---

#### Task 35: Create user AI insights views (subscription-based)

**What to do**:
- Create `resources/views/user/ai-insights/index.blade.php`
- Implement subscription check
- Display limited AI insights

**View Features**:
- Subscription gate (upgrade prompt if not subscribed)
- Limited clustering view
- No configuration options

**Acceptance Criteria**:
- [ ] View created
- [ ] Subscription check works
- [ ] Upgrade prompt displays
- [ ] Limited insights shown

---

#### Task 36: Create user layout and navigation

**What to do**:
- Update user sidebar (theme-specific)
- Add DEX Analytics menu item
- Handle multiple themes (default, blue, light, etc.)

**Menu Structure** (for each theme):
```blade
@if(AddonRegistry::active('dex-analytics-addon') && AddonRegistry::moduleEnabled('dex-analytics-addon', 'user_ui'))
<li class="nav-item">
    <a href="{{ route('user.dex-analytics.dashboard') }}" class="nav-link">
        <i class="nav-icon fas fa-chart-line"></i>
        <p>DEX Analytics</p>
    </a>
</li>
@endif
```

**Acceptance Criteria**:
- [ ] Menu added to all theme sidebars
- [ ] Active state works
- [ ] Permissions checked
- [ ] Module enablement checked

---

### Phase 7: Queue Jobs & Scheduling (Tasks 37-40)
*Keep original tasks 22-25, renumbered to 37-40*

---

### Phase 8: Testing & Documentation (Tasks 41-51) - EXPANDED

#### Task 41-44: Unit Tests
*Original task 26, expanded to 4 tasks*

**Task 41**: Test API client services
**Task 42**: Test normalization services
**Task 43**: Test analytics computation services
**Task 44**: Test AI intelligence services

---

#### Task 45-48: Feature Tests
*Original task 27, expanded to 4 tasks*

**Task 45**: Test admin routes and controllers
**Task 46**: Test user routes and controllers
**Task 47**: Test watchlist CRUD operations
**Task 48**: Test data ingestion flow

---

#### Task 49: Integration Tests

**What to do**:
- Test end-to-end flows
- Test platform API integrations
- Test job execution
- Test UI interactions

**Acceptance Criteria**:
- [ ] E2E tests created
- [ ] Platform integrations tested
- [ ] Jobs tested
- [ ] UI tested

---

#### Task 50: Performance Tests

**What to do**:
- Test polling performance
- Test analytics computation performance
- Test database query performance
- Test UI rendering performance

**Acceptance Criteria**:
- [ ] Performance benchmarks established
- [ ] Bottlenecks identified
- [ ] Optimizations implemented

---

#### Task 51: Create addon documentation
*Original task 28*

**What to do**:
- Create `README.md` in addon root
- Create user guide
- Create admin guide
- Create API documentation

**Acceptance Criteria**:
- [ ] README complete
- [ ] User guide complete
- [ ] Admin guide complete
- [ ] API docs complete

---

## Task Dependencies

### Critical Path
1. Tasks 1-5 (Skeleton) → MUST complete first
2. Tasks 6-10 (API Clients) → Depends on Task 1-5
3. Tasks 11-15 (Normalization) → Depends on Tasks 6-10
4. Tasks 16-18e (Analytics) → Depends on Tasks 11-15
5. Tasks 19-28 (Admin UI) → Depends on Tasks 16-18e
6. Tasks 29-36 (User UI) → Depends on Tasks 19-28
7. Tasks 37-40 (Jobs) → Depends on Tasks 11-15
8. Tasks 41-51 (Testing) → Depends on all previous tasks

### Parallelizable Tasks
- Tasks 6-10 (API clients can be built in parallel)
- Tasks 11-15 (Normalization services can be built in parallel)
- Tasks 19-28 (Admin UI views can be built in parallel after controllers)
- Tasks 29-36 (User UI views can be built in parallel after controllers)
- Tasks 41-48 (Tests can be written in parallel)

---

## Summary

**Total Tasks**: 57 (was 31)
**New UI Tasks**: 26
**Estimated Time**: 
- Original plan: ~80-100 hours
- With UI: ~140-160 hours

**Key Additions**:
- Detailed admin UI implementation (15 tasks)
- Complete user UI implementation (8 tasks)
- Expanded testing coverage (11 tasks)

**Ready for Implementation**: YES
