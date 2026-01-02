<?php

namespace Addons\TradingManagement\Modules\FilterStrategy\Controllers\User;

use App\Http\Controllers\Controller;
use App\Helpers\NotificationHelper;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FilterStrategyController extends Controller
{
    /**
     * Display a listing of the user's filter strategies
     */
    public function index()
    {
        try {
            $title = 'My Filter Strategies';
            
            if (!Schema::hasTable('filter_strategies')) {
                Log::warning('Filter strategies table does not exist');
                return view('trading-management::user.filter-strategy.index', [
                    'strategies' => collect([])->paginate(20),
                    'title' => $title
                ]);
            }
            
            $strategies = FilterStrategy::where('created_by_user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate(20);
            return view('trading-management::user.filter-strategy.index', compact('strategies', 'title'));
        } catch (\Exception $e) {
            Log::error('Filter strategies index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return view('trading-management::user.filter-strategy.index', [
                'strategies' => collect([])->paginate(20),
                'title' => 'My Filter Strategies'
            ]);
        }
    }

    /**
     * Display marketplace filter strategies
     */
    public function marketplace()
    {
        try {
            $title = 'Filter Strategies Marketplace';
            
            if (!Schema::hasTable('filter_strategies')) {
                Log::warning('Filter strategies table does not exist');
                return view('trading-management::user.filter-strategy.marketplace', [
                    'strategies' => collect([])->paginate(20),
                    'title' => $title
                ]);
            }
            
            $strategies = FilterStrategy::whereNull('created_by_user_id')
                ->where('visibility', 'PUBLIC_MARKETPLACE')
                ->orderBy('created_at', 'desc')
                ->paginate(20);
            return view('trading-management::user.filter-strategy.marketplace', compact('strategies', 'title'));
        } catch (\Exception $e) {
            Log::error('Filter strategies marketplace error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return view('trading-management::user.filter-strategy.marketplace', [
                'strategies' => collect([])->paginate(20),
                'title' => 'Filter Strategies Marketplace'
            ]);
        }
    }

    /**
     * Show the form for creating a new filter strategy
     */
    public function create()
    {
        $title = 'Create Filter Strategy';
        return view('trading-management::user.filter-strategy.create', compact('title'));
    }

    /**
     * Clone a filter strategy for the authenticated user
     */
    public function clone($id)
    {
        try {
            $strategy = FilterStrategy::findOrFail($id);
            
            if (!$strategy->canBeClonedBy(auth()->id())) {
                return back()->with('notify', NotificationHelper::error(__('This strategy cannot be cloned.'), 'Error'));
            }
            
            $clonedStrategy = $strategy->cloneForUser(auth()->id());
            
            return back()->with('notify', NotificationHelper::success(__('Strategy cloned successfully!'), 'Success'));
        } catch (\Exception $e) {
            Log::error('Filter strategy clone error: ' . $e->getMessage());
            return back()->with('notify', NotificationHelper::error(__('Failed to clone strategy. Please try again.'), 'Error'));
        }
    }
}
