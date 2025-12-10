<?php

namespace App\Http\Controllers\User\Trading;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class MarketplacesController extends Controller
{
    /**
     * Display unified Marketplaces page with categories
     */
    public function index(Request $request)
    {
        $data['title'] = __('Marketplaces');
        $data['activeCategory'] = $request->get('category', 'trading-presets');
        
        // Check if addon is enabled
        $data['tradingManagementEnabled'] = \App\Support\AddonRegistry::active('trading-management-addon');

        // Initialize items as empty collection if not set
        if (!isset($data['items'])) {
            $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
        }

        if ($data['tradingManagementEnabled']) {
            // Trading Presets category
            if ($data['activeCategory'] === 'trading-presets') {
                if (class_exists(\Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::class)) {
                    try {
                        $query = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::whereNull('created_by_user_id')
                            ->where('visibility', 'PUBLIC_MARKETPLACE');
                        
                        $count = $query->count();
                        \Log::info('Marketplace: Trading presets query', [
                            'category' => 'trading-presets',
                            'count' => $count
                        ]);
                        
                        $data['items'] = $query->latest()->paginate(20, ['*'], 'presets_page');
                        
                        \Log::info('Marketplace: Trading presets paginated', [
                            'total' => $data['items']->total(),
                            'count' => $data['items']->count()
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Marketplace: Error loading trading presets', ['error' => $e->getMessage()]);
                        $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                    }
                }
            }

            // Filter Strategies category
            if ($data['activeCategory'] === 'filter-strategies') {
                \Log::info('Marketplace Controller: Processing filter-strategies category', [
                    'request_category' => $request->get('category'),
                    'activeCategory' => $data['activeCategory'],
                    'class_exists' => class_exists(\Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::class)
                ]);
                
                if (class_exists(\Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::class)) {
                    try {
                        // Query all public marketplace strategies (admin-owned: created_by_user_id is NULL)
                        $query = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::query()
                            ->whereNull('created_by_user_id')
                            ->where('visibility', 'PUBLIC_MARKETPLACE')
                            ->where('enabled', true);
                        
                        // Debug: Check total count
                        $totalCount = $query->count();
                        $allPublic = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::where('visibility', 'PUBLIC_MARKETPLACE')->count();
                        $allEnabled = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::where('enabled', true)->count();
                        $allNullCreated = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::whereNull('created_by_user_id')->count();
                        
                        \Log::info('Marketplace: Filter strategies query debug', [
                            'category' => 'filter-strategies',
                            'total_matching' => $totalCount,
                            'all_public' => $allPublic,
                            'all_enabled' => $allEnabled,
                            'all_null_created' => $allNullCreated,
                            'query_sql' => $query->toSql(),
                            'bindings' => $query->getBindings()
                        ]);
                        
                        $data['items'] = $query->latest()->paginate(20, ['*'], 'strategies_page');
                        
                        \Log::info('Marketplace: Filter strategies paginated result', [
                            'category' => 'filter-strategies',
                            'total' => $data['items']->total(),
                            'count' => $data['items']->count(),
                            'current_page' => $data['items']->currentPage(),
                            'names' => $data['items']->pluck('name')->toArray(),
                            'ids' => $data['items']->pluck('id')->toArray()
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Marketplace: Error loading filter strategies', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                    }
                } else {
                    \Log::warning('Marketplace: FilterStrategy model class not found');
                }
            } else {
                \Log::info('Marketplace Controller: NOT processing filter-strategies', [
                    'request_category' => $request->get('category'),
                    'activeCategory' => $data['activeCategory']
                ]);
            }

            // AI Model Profiles category
            if ($data['activeCategory'] === 'ai-profiles') {
                if (class_exists(\Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::class)) {
                    try {
                        $query = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::whereNull('created_by_user_id')
                            ->where('visibility', 'PUBLIC_MARKETPLACE')
                            ->where('enabled', true);
                        
                        $count = $query->count();
                        \Log::info('Marketplace: AI profiles query', [
                            'category' => 'ai-profiles',
                            'count' => $count
                        ]);
                        
                        $data['items'] = $query->latest()->paginate(20, ['*'], 'ai_profiles_page');
                        
                        \Log::info('Marketplace: AI profiles paginated', [
                            'total' => $data['items']->total(),
                            'count' => $data['items']->count()
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Marketplace: Error loading AI profiles', ['error' => $e->getMessage()]);
                        $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                    }
                }
            }

            // Copy Trading category
            if ($data['activeCategory'] === 'copy-trading') {
                // Load copy trading traders/subscriptions
                $data['items'] = collect([]); // Placeholder - implement when copy trading module is ready
            }

            // Bot Marketplace category
            if ($data['activeCategory'] === 'bot-marketplace') {
                if (class_exists(\Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class)) {
                    try {
                        $query = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::query();
                        
                        // Check which columns exist and filter accordingly
                        $tableName = (new \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot())->getTable();
                        $hasCreatedByUserId = Schema::hasColumn($tableName, 'created_by_user_id');
                        $hasIsAdminOwned = Schema::hasColumn($tableName, 'is_admin_owned');
                        $hasIsDefaultTemplate = Schema::hasColumn($tableName, 'is_default_template');
                        $hasVisibility = Schema::hasColumn($tableName, 'visibility');
                        
                        // Admin-owned bots: admin_id is NOT NULL OR user_id is NULL
                        // Also include default templates if column exists
                        $query->where(function ($q) use ($hasCreatedByUserId, $hasIsAdminOwned, $hasIsDefaultTemplate) {
                            // Always check admin_id (always exists) - admin-owned bots
                            $q->whereNotNull('admin_id')
                              ->orWhereNull('user_id');
                            
                            // If created_by_user_id exists, also check it (system templates)
                            if ($hasCreatedByUserId) {
                                $q->orWhereNull('created_by_user_id');
                            }
                            
                            // If is_admin_owned exists, also check it
                            if ($hasIsAdminOwned) {
                                $q->orWhere('is_admin_owned', true);
                            }
                            
                            // If is_default_template exists, also include default templates
                            if ($hasIsDefaultTemplate) {
                                $q->orWhere('is_default_template', true);
                            }
                        });
                        
                        // Only show public marketplace items if visibility column exists
                        if ($hasVisibility) {
                            $query->where('visibility', 'PUBLIC_MARKETPLACE');
                        }
                        
                        $data['items'] = $query->with(['exchangeConnection', 'tradingPreset', 'filterStrategy', 'aiModelProfile'])
                            ->latest()
                            ->paginate(20, ['*'], 'bots_page');
                    } catch (\Exception $e) {
                        \Log::error('Marketplace: Error loading trading bots', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                        $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                    }
                }
            }
        }

        return view(Helper::theme() . 'user.trading.marketplaces', $data);
    }
}
