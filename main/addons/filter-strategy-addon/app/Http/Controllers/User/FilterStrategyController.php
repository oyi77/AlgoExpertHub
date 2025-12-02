<?php

namespace Addons\FilterStrategyAddon\App\Http\Controllers\User;

use Addons\FilterStrategyAddon\App\Models\FilterStrategy;
use Addons\FilterStrategyAddon\App\Services\FilterStrategyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FilterStrategyController extends Controller
{
    protected FilterStrategyService $service;

    public function __construct(FilterStrategyService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $user = auth()->user();
        $strategies = FilterStrategy::where('created_by_user_id', $user->id)
            ->orWhere(function ($query) {
                $query->where('visibility', 'PUBLIC_MARKETPLACE');
            })
            ->with('owner')
            ->withCount('tradingPresets')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('filter-strategy-addon::user.filter-strategies.index', compact('strategies'));
    }

    public function marketplace()
    {
        $strategies = FilterStrategy::where('visibility', 'PUBLIC_MARKETPLACE')
            ->with('owner')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('filter-strategy-addon::user.filter-strategies.marketplace', compact('strategies'));
    }

    public function create()
    {
        return view('filter-strategy-addon::user.filter-strategies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:PRIVATE,PUBLIC_MARKETPLACE',
            'clonable' => 'boolean',
            'enabled' => 'boolean',
            'config' => 'nullable|json',
            // Form fields (will be converted to config)
            'enable_ema' => 'boolean',
            'ema_fast_period' => 'nullable|integer|min:1',
            'ema_slow_period' => 'nullable|integer|min:1',
            'enable_stoch' => 'boolean',
            'stoch_k' => 'nullable|integer|min:1',
            'stoch_d' => 'nullable|integer|min:1',
            'stoch_smooth' => 'nullable|integer|min:1',
            'enable_psar' => 'boolean',
            'psar_step' => 'nullable|numeric|min:0.01',
            'psar_max' => 'nullable|numeric|min:0.01',
            'rule_logic' => 'nullable|in:AND,OR',
            'custom_rules' => 'nullable|json',
        ]);

        // Convert form fields to config JSON if config not provided directly
        if (empty($validated['config']) || $validated['config'] === '{}') {
            $validated['config'] = $this->buildConfigFromForm($request);
        } else {
            // If config is string, decode it
            if (is_string($validated['config'])) {
                $validated['config'] = json_decode($validated['config'], true);
            }
        }

        try {
            $strategy = $this->service->create($validated, auth()->user());
            return redirect()->route('user.filter-strategies.index')
                ->with('success', 'Filter strategy created successfully');
        } catch (\Exception $e) {
            Log::error('Failed to create filter strategy', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create filter strategy: ' . $e->getMessage());
        }
    }

    protected function buildConfigFromForm(Request $request): array
    {
        $config = [
            'indicators' => [],
            'rules' => [
                'logic' => $request->input('rule_logic', 'AND'),
                'conditions' => [],
            ],
        ];

        // EMA
        if ($request->has('enable_ema') && $request->input('enable_ema')) {
            $config['indicators']['ema_fast'] = ['period' => (int)($request->input('ema_fast_period') ?: 10)];
            $config['indicators']['ema_slow'] = ['period' => (int)($request->input('ema_slow_period') ?: 100)];
        }

        // Stochastic
        if ($request->has('enable_stoch') && $request->input('enable_stoch')) {
            $config['indicators']['stoch'] = [
                'k' => (int)($request->input('stoch_k') ?: 14),
                'd' => (int)($request->input('stoch_d') ?: 3),
                'smooth' => (int)($request->input('stoch_smooth') ?: 3),
            ];
        }

        // PSAR
        if ($request->has('enable_psar') && $request->input('enable_psar')) {
            $config['indicators']['psar'] = [
                'step' => (float)($request->input('psar_step') ?: 0.02),
                'max' => (float)($request->input('psar_max') ?: 0.2),
            ];
        }

        // Rules
        $customRules = $request->input('custom_rules');
        if ($customRules) {
            try {
                $config['rules']['conditions'] = json_decode($customRules, true) ?: [];
            } catch (\Exception $e) {
                Log::warning('Failed to parse custom rules', ['error' => $e->getMessage()]);
            }
        }

        return $config;
    }

    public function show(FilterStrategy $filterStrategy)
    {
        $filterStrategy->load('owner');
        return view('filter-strategy-addon::user.filter-strategies.show', compact('filterStrategy'));
    }

    public function edit(FilterStrategy $filterStrategy)
    {
        $user = auth()->user();
        if (!$filterStrategy->canEditBy($user->id)) {
            abort(403, 'You do not have permission to edit this strategy');
        }

        return view('filter-strategy-addon::user.filter-strategies.edit', compact('filterStrategy'));
    }

    public function update(Request $request, FilterStrategy $filterStrategy)
    {
        $user = auth()->user();
        if (!$filterStrategy->canEditBy($user->id)) {
            abort(403, 'You do not have permission to update this strategy');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:PRIVATE,PUBLIC_MARKETPLACE',
            'clonable' => 'boolean',
            'enabled' => 'boolean',
            'config' => 'nullable|json',
            // Form fields (will be converted to config)
            'enable_ema' => 'boolean',
            'ema_fast_period' => 'nullable|integer|min:1',
            'ema_slow_period' => 'nullable|integer|min:1',
            'enable_stoch' => 'boolean',
            'stoch_k' => 'nullable|integer|min:1',
            'stoch_d' => 'nullable|integer|min:1',
            'stoch_smooth' => 'nullable|integer|min:1',
            'enable_psar' => 'boolean',
            'psar_step' => 'nullable|numeric|min:0.01',
            'psar_max' => 'nullable|numeric|min:0.01',
            'rule_logic' => 'nullable|in:AND,OR',
            'custom_rules' => 'nullable|json',
        ]);

        // Convert form fields to config JSON if config not provided directly
        if (empty($validated['config']) || $validated['config'] === '{}') {
            $validated['config'] = $this->buildConfigFromForm($request);
        } else {
            // If config is string, decode it
            if (is_string($validated['config'])) {
                $validated['config'] = json_decode($validated['config'], true);
            }
        }

        try {
            $this->service->update($filterStrategy, $validated, $user);
            return redirect()->route('user.filter-strategies.index')
                ->with('success', 'Filter strategy updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update filter strategy', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update filter strategy: ' . $e->getMessage());
        }
    }

    public function destroy(FilterStrategy $filterStrategy)
    {
        $user = auth()->user();
        if (!$filterStrategy->canEditBy($user->id)) {
            abort(403, 'You do not have permission to delete this strategy');
        }

        try {
            $this->service->delete($filterStrategy, $user);
            return redirect()->route('user.filter-strategies.index')
                ->with('success', 'Filter strategy deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete filter strategy', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to delete filter strategy: ' . $e->getMessage());
        }
    }

    public function clone(FilterStrategy $filterStrategy)
    {
        try {
            $cloned = $this->service->clone($filterStrategy, auth()->user());
            return redirect()->route('user.filter-strategies.edit', $cloned)
                ->with('success', 'Filter strategy cloned successfully');
        } catch (\Exception $e) {
            Log::error('Failed to clone filter strategy', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to clone filter strategy: ' . $e->getMessage());
        }
    }

    public function clone(FilterStrategy $filterStrategy)
    {
        try {
            $cloned = $this->service->clone($filterStrategy, auth()->user());
            return redirect()->route('user.filter-strategies.edit', $cloned)
                ->with('success', 'Filter strategy cloned successfully');
        } catch (\Exception $e) {
            Log::error('Failed to clone filter strategy', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to clone filter strategy: ' . $e->getMessage());
        }
    }
}

