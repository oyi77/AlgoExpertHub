<?php

namespace Addons\TradingManagement\Modules\FilterStrategy\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Illuminate\Http\Request;

class FilterStrategyController extends Controller
{
    public function index()
    {
        $title = 'Filter Strategies';
        $strategies = FilterStrategy::with('owner')->orderBy('created_at', 'desc')->paginate(20);
        return view('trading-management::backend.trading-management.strategy.filters.index', compact('title', 'strategies'));
    }

    public function create()
    {
        $title = 'Create Filter Strategy';
        return view('trading-management::backend.trading-management.strategy.filters.create', compact('title'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:PRIVATE,PUBLIC_MARKETPLACE',
            'config' => 'required|array',
        ]);

        $strategy = FilterStrategy::create([
            ...$validated,
            'enabled' => true,
            'clonable' => $request->boolean('clonable'),
        ]);

        return redirect()->route('admin.trading-management.strategy.filters.index')
            ->with('success', 'Filter strategy created successfully');
    }

    public function edit(FilterStrategy $filter)
    {
        $title = 'Edit Filter Strategy';
        $strategy = $filter;
        return view('trading-management::backend.trading-management.strategy.filters.edit', compact('title', 'strategy'));
    }

    public function update(Request $request, FilterStrategy $filter)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:PRIVATE,PUBLIC_MARKETPLACE',
            'config' => 'required|array',
            'enabled' => 'sometimes|boolean',
        ]);

        $filter->update([
            ...$validated,
            'clonable' => $request->boolean('clonable'),
        ]);

        return redirect()->route('admin.trading-management.strategy.filters.index')
            ->with('success', 'Filter strategy updated successfully');
    }

    public function destroy(FilterStrategy $filter)
    {
        $filter->delete();
        return redirect()->route('admin.trading-management.strategy.filters.index')
            ->with('success', 'Filter strategy deleted successfully');
    }
}
