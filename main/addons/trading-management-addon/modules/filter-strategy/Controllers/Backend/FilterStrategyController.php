<?php

namespace Addons\TradingManagement\Modules\FilterStrategy\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Illuminate\Http\Request;

class FilterStrategyController extends Controller
{
    public function index()
    {
        $strategies = FilterStrategy::with('owner')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('trading-management::backend.trading-management.strategy.filters.index', compact('strategies'));
    }

    public function create()
    {
        return view('trading-management::backend.trading-management.strategy.filters.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'config' => 'required|json',
            'visibility' => 'required|in:PRIVATE,PUBLIC_MARKETPLACE',
            'clonable' => 'boolean',
            'enabled' => 'boolean',
        ]);

        $validated['config'] = json_decode($validated['config'], true);

        FilterStrategy::create($validated);

        return redirect()->route('admin.trading-management.strategy.filters.index')
            ->with('success', 'Filter strategy created successfully');
    }

    public function edit(FilterStrategy $filterStrategy)
    {
        return view('trading-management::backend.trading-management.strategy.filters.edit', [
            'strategy' => $filterStrategy,
        ]);
    }

    public function update(Request $request, FilterStrategy $filterStrategy)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'config' => 'required|json',
            'visibility' => 'required|in:PRIVATE,PUBLIC_MARKETPLACE',
            'clonable' => 'boolean',
            'enabled' => 'boolean',
        ]);

        $validated['config'] = json_decode($validated['config'], true);

        $filterStrategy->update($validated);

        return redirect()->route('admin.trading-management.strategy.filters.index')
            ->with('success', 'Filter strategy updated successfully');
    }

    public function destroy(FilterStrategy $filterStrategy)
    {
        $filterStrategy->delete();

        return redirect()->route('admin.trading-management.strategy.filters.index')
            ->with('success', 'Filter strategy deleted successfully');
    }
}

