<?php

namespace Addons\TradingManagement\Modules\TradingBot\Controllers\User;

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
     * Display a listing of user's trading bots
     */
    public function index(Request $request): View
    {
        $data['title'] = 'My Trading Bots';
        
        $filters = [
            'is_active' => $request->get('is_active'),
            'is_paper_trading' => $request->get('is_paper_trading'),
            'search' => $request->get('search'),
            'per_page' => 15,
        ];

        $data['bots'] = $this->botService->getBots($filters);
        
        // Statistics
        $allBots = TradingBot::forUser(auth()->id())->get();
        $data['stats'] = [
            'total' => $allBots->count(),
            'active' => $allBots->where('is_active', true)->count(),
            'paper_trading' => $allBots->where('is_paper_trading', true)->count(),
            'total_profit' => $allBots->sum('total_profit'),
        ];

        return view('trading-management::user.trading-bots.index', $data);
    }

    /**
     * Show the form for creating a new trading bot
     */
    public function create(): View
    {
        $data['title'] = 'Create Trading Bot';
        $data['bot'] = null;
        
        // Get available options
        $data['connections'] = $this->botService->getAvailableConnections();
        $data['presets'] = $this->botService->getAvailablePresets();
        $data['filterStrategies'] = $this->botService->getAvailableFilterStrategies();
        $data['aiProfiles'] = $this->botService->getAvailableAiProfiles();

        return view('trading-management::user.trading-bots.create', $data);
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

        $validated['is_paper_trading'] = $validated['is_paper_trading'] ?? true; // Default to paper trading for demo

        try {
            $bot = $this->botService->create($validated);
            
            return redirect()
                ->route('user.trading-bots.show', $bot->id)
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
        $bot = TradingBot::with(['exchangeConnection', 'tradingPreset', 'filterStrategy', 'aiModelProfile'])
            ->forUser(auth()->id())
            ->findOrFail($id);

        $data['title'] = $bot->name;
        $data['bot'] = $bot;

        // Get recent executions (if execution_logs has trading_bot_id)
        // TODO: Add relationship when execution_logs table is updated
        $data['recentExecutions'] = collect(); // Placeholder

        return view('trading-management::user.trading-bots.show', $data);
    }

    /**
     * Show the form for editing the specified trading bot
     */
    public function edit($id): View
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);

        $data['title'] = 'Edit Trading Bot';
        $data['bot'] = $bot;
        
        // Get available options
        $data['connections'] = $this->botService->getAvailableConnections();
        $data['presets'] = $this->botService->getAvailablePresets();
        $data['filterStrategies'] = $this->botService->getAvailableFilterStrategies();
        $data['aiProfiles'] = $this->botService->getAvailableAiProfiles();

        return view('trading-management::user.trading-bots.edit', $data);
    }

    /**
     * Update the specified trading bot
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);

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
                ->route('user.trading-bots.show', $bot->id)
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
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);

        try {
            $this->botService->delete($bot);
            
            return redirect()
                ->route('user.trading-bots.index')
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
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);

        try {
            $this->botService->toggleActive($bot);
            
            $status = $bot->fresh()->is_active ? 'activated' : 'deactivated';
            
            return redirect()
                ->back()
                ->with('success', "Trading bot {$status} successfully!");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to toggle bot status: ' . $e->getMessage());
        }
    }
}
