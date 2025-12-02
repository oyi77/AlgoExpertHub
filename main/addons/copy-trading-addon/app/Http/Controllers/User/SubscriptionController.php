<?php

namespace Addons\CopyTrading\App\Http\Controllers\User;

use Addons\CopyTrading\App\Http\Controllers\Controller;
use Addons\CopyTrading\App\Models\CopyTradingSubscription;
use Addons\CopyTrading\App\Services\CopyTradingService;
use App\Helpers\Helper\Helper;
use App\Support\AddonRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    protected CopyTradingService $copyTradingService;

    public function __construct(CopyTradingService $copyTradingService)
    {
        $this->copyTradingService = $copyTradingService;
    }

    /**
     * Show my subscriptions.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $subscriptions = $this->copyTradingService->getFollowerSubscriptions($user->id);

        $data['title'] = 'My Subscriptions';
        $data['subscriptions'] = $subscriptions;

        return view('copy-trading::user.subscriptions.index', $data);
    }

    /**
     * Show form to subscribe to a trader.
     */
    public function create(int $traderId): View
    {
        $user = auth()->user();

        // Check if trading execution engine is available
        if (!AddonRegistry::active('trading-execution-engine-addon') 
            || !class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
            return redirect()->route('user.copy-trading.traders.index')
                ->with('error', 'Trading execution engine is required for copy trading');
        }

        // Get user's active connections
        $connections = \Addons\TradingExecutionEngine\App\Models\ExecutionConnection::userOwned()
            ->where('user_id', $user->id)
            ->active()
            ->get();

        if ($connections->isEmpty()) {
            return redirect()->route('user.execution-connections.index')
                ->with('error', 'You need at least one active connection to copy trades');
        }

        $trader = \App\Models\User::findOrFail($traderId);
        $traderSetting = \Addons\CopyTrading\App\Models\CopyTradingSetting::byUser($traderId)
            ->enabled()
            ->firstOrFail();

        $data['title'] = 'Subscribe to Trader';
        $data['trader'] = $trader;
        $data['trader_setting'] = $traderSetting;
        $data['connections'] = $connections;

        return view('copy-trading::user.subscriptions.create', $data);
    }

    /**
     * Store subscription.
     */
    public function store(Request $request, int $traderId): RedirectResponse
    {
        $user = auth()->user();

        $request->validate([
            'connection_id' => 'required|exists:execution_connections,id',
            'copy_mode' => 'required|in:easy,advanced',
            'risk_multiplier' => 'required_if:copy_mode,easy|numeric|min:0.1|max:10',
            'max_position_size' => 'nullable|numeric|min:0',
            // Advanced mode settings
            'method' => 'required_if:copy_mode,advanced|in:percentage,fixed_quantity',
            'percentage' => 'required_if:method,percentage|numeric|min:0.01|max:100',
            'fixed_quantity' => 'required_if:method,fixed_quantity|numeric|min:0.00000001',
            'min_quantity' => 'nullable|numeric|min:0',
            'max_quantity' => 'nullable|numeric|min:0',
        ]);

        // Check if trading execution engine is available
        if (!AddonRegistry::active('trading-execution-engine-addon') 
            || !class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Trading execution engine is required for copy trading');
        }

        // Validate connection belongs to user
        $connection = \Addons\TradingExecutionEngine\App\Models\ExecutionConnection::findOrFail($request->connection_id);
        if ($connection->user_id !== $user->id) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid connection');
        }

        try {
            $settings = [
                'max_position_size' => $request->max_position_size,
            ];

            if ($request->copy_mode === 'easy') {
                $settings['risk_multiplier'] = $request->risk_multiplier;
            } else {
                $settings['copy_settings'] = [
                    'method' => $request->method,
                    'percentage' => $request->method === 'percentage' ? $request->percentage : null,
                    'fixed_quantity' => $request->method === 'fixed_quantity' ? $request->fixed_quantity : null,
                    'min_quantity' => $request->min_quantity,
                    'max_quantity' => $request->max_quantity,
                ];
            }

            $this->copyTradingService->subscribe(
                $user->id,
                $traderId,
                $request->connection_id,
                $request->copy_mode,
                $settings
            );

            return redirect()->route('user.copy-trading.subscriptions.index')
                ->with('success', 'Successfully subscribed to trader');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show edit subscription form.
     */
    public function edit(int $id): View
    {
        $user = auth()->user();
        $subscription = CopyTradingSubscription::where('id', $id)
            ->where('follower_id', $user->id)
            ->with(['trader', 'connection'])
            ->firstOrFail();

        // Check if trading execution engine is available
        if (!AddonRegistry::active('trading-execution-engine-addon') 
            || !class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
            return redirect()->route('user.copy-trading.subscriptions.index')
                ->with('error', 'Trading execution engine is required for copy trading');
        }

        $connections = \Addons\TradingExecutionEngine\App\Models\ExecutionConnection::userOwned()
            ->where('user_id', $user->id)
            ->active()
            ->get();

        $data['title'] = 'Edit Subscription';
        $data['subscription'] = $subscription;
        $data['connections'] = $connections;

        return view('copy-trading::user.subscriptions.edit', $data);
    }

    /**
     * Update subscription.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = auth()->user();

        $request->validate([
            'connection_id' => 'required|exists:execution_connections,id',
            'copy_mode' => 'required|in:easy,advanced',
            'risk_multiplier' => 'required_if:copy_mode,easy|numeric|min:0.1|max:10',
            'max_position_size' => 'nullable|numeric|min:0',
            'method' => 'required_if:copy_mode,advanced|in:percentage,fixed_quantity',
            'percentage' => 'required_if:method,percentage|numeric|min:0.01|max:100',
            'fixed_quantity' => 'required_if:method,fixed_quantity|numeric|min:0.00000001',
            'min_quantity' => 'nullable|numeric|min:0',
            'max_quantity' => 'nullable|numeric|min:0',
        ]);

        // Check if trading execution engine is available
        if (!AddonRegistry::active('trading-execution-engine-addon') 
            || !class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Trading execution engine is required for copy trading');
        }

        // Validate connection belongs to user
        $connection = \Addons\TradingExecutionEngine\App\Models\ExecutionConnection::findOrFail($request->connection_id);
        if ($connection->user_id !== $user->id) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid connection');
        }

        $data = [
            'connection_id' => $request->connection_id,
            'copy_mode' => $request->copy_mode,
            'max_position_size' => $request->max_position_size,
        ];

        if ($request->copy_mode === 'easy') {
            $data['risk_multiplier'] = $request->risk_multiplier;
        } else {
            $data['copy_settings'] = [
                'method' => $request->method,
                'percentage' => $request->method === 'percentage' ? $request->percentage : null,
                'fixed_quantity' => $request->method === 'fixed_quantity' ? $request->fixed_quantity : null,
                'min_quantity' => $request->min_quantity,
                'max_quantity' => $request->max_quantity,
            ];
        }

        $this->copyTradingService->updateSubscription($id, $user->id, $data);

        return redirect()->route('user.copy-trading.subscriptions.index')
            ->with('success', 'Subscription updated successfully');
    }

    /**
     * Unsubscribe from trader.
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = auth()->user();
        $subscription = CopyTradingSubscription::where('id', $id)
            ->where('follower_id', $user->id)
            ->firstOrFail();

        $traderId = $subscription->trader_id;
        $this->copyTradingService->unsubscribe($user->id, $traderId);

        return redirect()->route('user.copy-trading.subscriptions.index')
            ->with('success', 'Unsubscribed successfully');
    }
}

