<?php

namespace App\Http\Controllers\User\Trading;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

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
                if (class_exists(\Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::class)) {
                    try {
                        // Query all public marketplace strategies (admin-owned: created_by_user_id is NULL)
                        // Note: Model uses SoftDeletes, so withTrashed() is not needed for active items
                        $query = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::query()
                            ->whereNull('created_by_user_id')
                            ->where('visibility', 'PUBLIC_MARKETPLACE')
                            ->where('enabled', true);
                        
                        $data['items'] = $query->latest()->paginate(20, ['*'], 'strategies_page');
                    } catch (\Exception $e) {
                        \Log::error('Marketplace: Error loading filter strategies', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                    }
                }
            }

            // AI Model Profiles category
            if ($data['activeCategory'] === 'ai-profiles') {
                if (class_exists(\Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::class)) {
                    try {
                        // Check if table exists
                        $tableName = (new \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile())->getTable();
                        if (!Schema::hasTable($tableName)) {
                            \Log::warning('Marketplace: AI model profiles table does not exist');
                            $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                        } else {
                            $query = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::query()
                                ->whereNull('created_by_user_id')
                                ->where('visibility', 'PUBLIC_MARKETPLACE');
                            
                            // Only filter by enabled if column exists and we want to show only enabled
                            if (Schema::hasColumn($tableName, 'enabled')) {
                                $query->where('enabled', true);
                            }
                            
                            // Eager load relationships if they exist
                            if (Schema::hasColumn($tableName, 'ai_connection_id')) {
                                $query->with('aiConnection');
                            }
                            
                            $count = $query->count();
                            \Log::info('Marketplace: AI profiles query', [
                                'category' => 'ai-profiles',
                                'count' => $count,
                                'table' => $tableName
                            ]);
                            
                            $data['items'] = $query->latest()->paginate(20, ['*'], 'ai_profiles_page');
                            
                            \Log::info('Marketplace: AI profiles paginated', [
                                'total' => $data['items']->total(),
                                'count' => $data['items']->count()
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Marketplace: Error loading AI profiles', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                        $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                    }
                } else {
                    \Log::warning('Marketplace: AiModelProfile class does not exist');
                    $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
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
                        $tableName = (new \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot())->getTable();
                        
                        // Check if table exists
                        if (!Schema::hasTable($tableName)) {
                            \Log::warning('Marketplace: Trading bots table does not exist');
                            $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                        } else {
                            $query = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::query();
                            
                            // Check which columns exist
                            $hasVisibility = Schema::hasColumn($tableName, 'visibility');
                            $hasCreatedByUserId = Schema::hasColumn($tableName, 'created_by_user_id');
                            $hasIsDefaultTemplate = Schema::hasColumn($tableName, 'is_default_template');
                            $hasIsAdminOwned = Schema::hasColumn($tableName, 'is_admin_owned');
                            
                            // Build query: Show bots that are either:
                            // 1. Public marketplace (visibility = 'PUBLIC_MARKETPLACE'), OR
                            // 2. Admin-owned templates (admin_id NOT NULL OR user_id NULL OR created_by_user_id NULL OR is_default_template = true)
                            $query->where(function ($q) use ($hasVisibility, $hasCreatedByUserId, $hasIsDefaultTemplate, $hasIsAdminOwned) {
                                // Option 1: Public marketplace bots
                                if ($hasVisibility) {
                                    $q->where('visibility', 'PUBLIC_MARKETPLACE');
                                }
                                
                                // Option 2: Admin-owned or template bots
                                // Admin-owned: admin_id is NOT NULL
                                $q->orWhereNotNull('admin_id');
                                
                                // OR user_id is NULL (system bots)
                                $q->orWhereNull('user_id');
                                
                                // OR created_by_user_id is NULL (system templates)
                                if ($hasCreatedByUserId) {
                                    $q->orWhereNull('created_by_user_id');
                                }
                                
                                // OR is_default_template is true
                                if ($hasIsDefaultTemplate) {
                                    $q->orWhere('is_default_template', true);
                                }
                                
                                // OR is_admin_owned is true
                                if ($hasIsAdminOwned) {
                                    $q->orWhere('is_admin_owned', true);
                                }
                            });
                            
                            $count = $query->count();
                            \Log::info('Marketplace: Trading bots query', [
                                'category' => 'bot-marketplace',
                                'count' => $count,
                                'table' => $tableName,
                                'hasVisibility' => $hasVisibility,
                                'hasCreatedByUserId' => $hasCreatedByUserId,
                                'hasIsDefaultTemplate' => $hasIsDefaultTemplate,
                                'hasIsAdminOwned' => $hasIsAdminOwned
                            ]);
                            
                            $data['items'] = $query->with(['exchangeConnection', 'tradingPreset', 'filterStrategy', 'aiModelProfile'])
                                ->latest()
                                ->paginate(20, ['*'], 'bots_page');
                            
                            \Log::info('Marketplace: Trading bots paginated', [
                                'total' => $data['items']->total(),
                                'count' => $data['items']->count()
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Marketplace: Error loading trading bots', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                        $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                    }
                } else {
                    \Log::warning('Marketplace: TradingBot class does not exist');
                    $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                }
            }
        }

        return view(Helper::themeView('user.trading.marketplaces'), $data);
    }

    // ========== BETA METHOD ==========

    public function betaIndex(Request $request)
    {
        $data['title'] = __('Marketplaces');
        $data['activeCategory'] = $request->get('category', 'trading-presets');
        $data['tradingManagementEnabled'] = \App\Support\AddonRegistry::active('trading-management-addon');
        $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);

        if ($data['tradingManagementEnabled']) {
            try {
                if ($data['activeCategory'] === 'trading-presets') {
                    if (class_exists(\Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::class)) {
                        try {
                            $query = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::whereNull('created_by_user_id')
                                ->where('visibility', 'PUBLIC_MARKETPLACE');
                            $data['items'] = $query->latest()->paginate(20);
                        } catch (\Exception $e) {
                            $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                        }
                    }
                }

                if ($data['activeCategory'] === 'filter-strategies') {
                    if (class_exists(\Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::class)) {
                        try {
                            $query = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::query()
                                ->whereNull('created_by_user_id')
                                ->where('visibility', 'PUBLIC_MARKETPLACE')
                                ->where('enabled', true);
                            $data['items'] = $query->latest()->paginate(20);
                        } catch (\Exception $e) {
                            $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                        }
                    }
                }

                if ($data['activeCategory'] === 'ai-profiles') {
                    if (class_exists(\Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::class)) {
                        try {
                            $tableName = (new \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile())->getTable();
                            if (Schema::hasTable($tableName)) {
                                $query = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::query()
                                    ->whereNull('created_by_user_id')
                                    ->where('visibility', 'PUBLIC_MARKETPLACE');
                                if (Schema::hasColumn($tableName, 'enabled')) {
                                    $query->where('enabled', true);
                                }
                                $data['items'] = $query->latest()->paginate(20);
                            }
                        } catch (\Exception $e) {
                            $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                        }
                    }
                }

                if ($data['activeCategory'] === 'bot-marketplace') {
                    if (class_exists(\Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class)) {
                        try {
                            $tableName = (new \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot())->getTable();
                            if (Schema::hasTable($tableName)) {
                                $query = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::query();
                                $hasVisibility = Schema::hasColumn($tableName, 'visibility');
                                $hasCreatedByUserId = Schema::hasColumn($tableName, 'created_by_user_id');
                                
                                $query->where(function ($q) use ($hasVisibility, $hasCreatedByUserId) {
                                    if ($hasVisibility) {
                                        $q->where('visibility', 'PUBLIC_MARKETPLACE');
                                    }
                                    $q->orWhereNotNull('admin_id');
                                    $q->orWhereNull('user_id');
                                    if ($hasCreatedByUserId) {
                                        $q->orWhereNull('created_by_user_id');
                                    }
                                });
                                
                                $data['items'] = $query->with(['exchangeConnection', 'tradingPreset'])->latest()->paginate(20);
                            }
                        } catch (\Exception $e) {
                            $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                        }
                    }
                }
            } catch (\Exception $e) {
                $data['items'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
            }
        }

        return Inertia::render('User/Marketplaces', $data);
    }
}
