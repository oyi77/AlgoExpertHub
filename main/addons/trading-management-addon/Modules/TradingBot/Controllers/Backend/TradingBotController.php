<?php

namespace Addons\TradingManagement\Modules\TradingBot\Controllers\Backend;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TradingBotController extends Controller
{
    protected TradingBotService $botService;

    public function __construct(TradingBotService $botService)
    {
        $this->botService = $botService;
    }

    /**
     * Display a listing of all trading bots (admin + users)
     */
    public function index(Request $request): View
    {
        $data['title'] = 'Trading Bots';
        
        $filters = [
            'is_active' => $request->get('is_active'),
            'is_paper_trading' => $request->get('is_paper_trading'),
            'user_id' => $request->get('user_id'),
            'admin_id' => $request->get('admin_id'),
            'search' => $request->get('search'),
            'per_page' => 20,
        ];

        // Admin can see all bots (user + admin owned)
        $data['bots'] = TradingBot::with(['user', 'admin', 'exchangeConnection', 'tradingPreset', 'filterStrategy', 'aiModelProfile'])
            ->when($filters['is_active'] !== null, function ($query) use ($filters) {
                return $query->where('is_active', $filters['is_active']);
            })
            ->when($filters['is_paper_trading'] !== null, function ($query) use ($filters) {
                return $query->where('is_paper_trading', $filters['is_paper_trading']);
            })
            ->when($filters['user_id'], function ($query) use ($filters) {
                return $query->where('user_id', $filters['user_id']);
            })
            ->when($filters['admin_id'], function ($query) use ($filters) {
                return $query->where('admin_id', $filters['admin_id']);
            })
            ->when($filters['search'], function ($query) use ($filters) {
                return $query->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page']);

        // Statistics
        $allBots = TradingBot::withTrashed()->get();
        $data['stats'] = [
            'total' => $allBots->count(),
            'active' => $allBots->where('is_active', true)->count(),
            'paper_trading' => $allBots->where('is_paper_trading', true)->count(),
            'user_bots' => $allBots->whereNotNull('user_id')->count(),
            'admin_bots' => $allBots->whereNotNull('admin_id')->count(),
            'total_profit' => $allBots->sum('total_profit'),
        ];

        // Get available users and admins for filtering
        $data['users'] = \App\Models\User::select('id', 'username', 'email')->orderBy('username')->get();
        $data['admins'] = \App\Models\Admin::select('id', 'username', 'email')->orderBy('username')->get();

        return view('trading-management::backend.trading-bots.index', $data);
    }

    /**
     * Show the form for creating a new trading bot (admin-owned)
     */
    public function create(): View
    {
        $data['title'] = 'Create Trading Bot';
        $data['bot'] = null;
        
        $data['connections'] = $this->botService->getAvailableConnections();
        $data['presets'] = $this->botService->getAvailablePresets();
        $data['filterStrategies'] = $this->botService->getAvailableFilterStrategies();
        $data['aiProfiles'] = $this->botService->getAvailableAiProfiles();

        return view('trading-management::backend.trading-bots.create', $data);
    }

    /**
     * Store a newly created trading bot
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exchange_connection_id' => 'required|exists:exchange_connections,id',
            'trading_preset_id' => 'required|exists:trading_presets,id',
            'filter_strategy_id' => 'nullable|exists:filter_strategies,id',
            'ai_model_profile_id' => 'nullable|exists:ai_model_profiles,id',
            'is_paper_trading' => 'boolean',
        ]);

        $validated['admin_id'] = auth()->guard('admin')->id();
        $validated['is_active'] = $request->get('is_active', true);

        try {
            $bot = $this->botService->create($validated);
            
            return redirect()
                ->route('admin.trading-management.trading-bots.show', $bot->id)
                ->with('success', 'Trading bot created successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create trading bot: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified trading bot
     */
    public function show($id): View
    {
        $bot = TradingBot::with(['user', 'admin', 'exchangeConnection', 'tradingPreset', 'filterStrategy', 'aiModelProfile'])
            ->findOrFail($id);

        $data['title'] = 'Trading Bot: ' . $bot->name;
        $data['bot'] = $bot;

        // Get execution logs for this bot
        $data['executions'] = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::where('trading_bot_id', $bot->id)
            ->with(['signal', 'executionConnection'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('trading-management::backend.trading-bots.show', $data);
    }

    /**
     * Show the form for editing the specified trading bot
     */
    public function edit($id): View
    {
        $bot = TradingBot::findOrFail($id);
        
        $data['title'] = 'Edit Trading Bot';
        $data['bot'] = $bot;
        
        $data['connections'] = $this->botService->getAvailableConnections();
        $data['presets'] = $this->botService->getAvailablePresets();
        $data['filterStrategies'] = $this->botService->getAvailableFilterStrategies();
        $data['aiProfiles'] = $this->botService->getAvailableAiProfiles();

        return view('trading-management::backend.trading-bots.edit', $data);
    }

    /**
     * Update the specified trading bot
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $bot = TradingBot::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exchange_connection_id' => 'required|exists:exchange_connections,id',
            'trading_preset_id' => 'required|exists:trading_presets,id',
            'filter_strategy_id' => 'nullable|exists:filter_strategies,id',
            'ai_model_profile_id' => 'nullable|exists:ai_model_profiles,id',
            'is_paper_trading' => 'boolean',
        ]);

        try {
            $this->botService->update($bot, $validated);
            
            return redirect()
                ->route('admin.trading-management.trading-bots.show', $bot->id)
                ->with('success', 'Trading bot updated successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update trading bot: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified trading bot
     */
    public function destroy($id): RedirectResponse
    {
        $bot = TradingBot::findOrFail($id);

        try {
            $this->botService->delete($bot);
            
            return redirect()
                ->route('admin.trading-management.trading-bots.index')
                ->with('success', 'Trading bot deleted successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete trading bot: ' . $e->getMessage());
        }
    }

    /**
     * Toggle bot active status
     */
    public function toggleActive($id): RedirectResponse
    {
        $bot = TradingBot::findOrFail($id);

        try {
            $this->botService->toggleActive($bot);
            
            return redirect()
                ->back()
                ->with('success', 'Trading bot status updated!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to update bot status: ' . $e->getMessage());
        }
    }
}
