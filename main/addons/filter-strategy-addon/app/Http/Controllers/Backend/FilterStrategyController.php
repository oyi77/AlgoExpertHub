<?php

namespace Addons\FilterStrategyAddon\App\Http\Controllers\Backend;

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
        $strategies = FilterStrategy::with('owner')
            ->withCount('tradingPresets')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => FilterStrategy::count(),
            'enabled' => FilterStrategy::where('enabled', true)->count(),
            'public' => FilterStrategy::where('visibility', 'PUBLIC_MARKETPLACE')->count(),
        ];

        return view('filter-strategy-addon::backend.filter-strategies.index', compact('strategies', 'stats'));
    }

    public function create()
    {
        return view('filter-strategy-addon::backend.filter-strategies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:PRIVATE,PUBLIC_MARKETPLACE',
            'clonable' => 'boolean',
            'enabled' => 'boolean',
            'config' => 'required|json',
        ]);

        try {
            $strategy = $this->service->create($validated, auth()->guard('admin')->user());
            return redirect()->route('admin.filter-strategies.index')
                ->with('success', 'Filter strategy created successfully');
        } catch (\Exception $e) {
            Log::error('Failed to create filter strategy', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create filter strategy: ' . $e->getMessage());
        }
    }

    public function show(FilterStrategy $filterStrategy)
    {
        $filterStrategy->load('owner');
        return view('filter-strategy-addon::backend.filter-strategies.show', compact('filterStrategy'));
    }

    public function edit(FilterStrategy $filterStrategy)
    {
        return view('filter-strategy-addon::backend.filter-strategies.edit', compact('filterStrategy'));
    }

    public function update(Request $request, FilterStrategy $filterStrategy)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:PRIVATE,PUBLIC_MARKETPLACE',
            'clonable' => 'boolean',
            'enabled' => 'boolean',
            'config' => 'required|json',
        ]);

        try {
            $this->service->update($filterStrategy, $validated, auth()->guard('admin')->user());
            return redirect()->route('admin.filter-strategies.index')
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
        try {
            $this->service->delete($filterStrategy, auth()->guard('admin')->user());
            return redirect()->route('admin.filter-strategies.index')
                ->with('success', 'Filter strategy deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete filter strategy', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to delete filter strategy: ' . $e->getMessage());
        }
    }
}

