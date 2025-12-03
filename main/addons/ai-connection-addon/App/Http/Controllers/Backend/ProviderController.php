<?php

namespace Addons\AiConnectionAddon\App\Http\Controllers\Backend;

use Addons\AiConnectionAddon\App\Models\AiProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProviderController extends Controller
{
    /**
     * Display a listing of providers
     */
    public function index()
    {
        $providers = AiProvider::withCount('connections')->latest()->get();

        return view('ai-connection-addon::backend.providers.index', compact('providers'));
    }

    /**
     * Show the form for creating a new provider
     */
    public function create()
    {
        return view('ai-connection-addon::backend.providers.create');
    }

    /**
     * Store a newly created provider
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:ai_providers,slug|alpha_dash',
            'status' => 'required|in:active,inactive',
        ]);

        AiProvider::create($request->only(['name', 'slug', 'status']));

        return redirect()->route('admin.ai-connections.providers.index')
            ->with('success', 'Provider created successfully');
    }

    /**
     * Show the form for editing the specified provider
     */
    public function edit(AiProvider $provider)
    {
        return view('ai-connection-addon::backend.providers.edit', compact('provider'));
    }

    /**
     * Update the specified provider
     */
    public function update(Request $request, AiProvider $provider)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|alpha_dash|unique:ai_providers,slug,' . $provider->id,
            'status' => 'required|in:active,inactive',
        ]);

        $provider->update($request->only(['name', 'slug', 'status']));

        return redirect()->route('admin.ai-connections.providers.index')
            ->with('success', 'Provider updated successfully');
    }

    /**
     * Remove the specified provider
     */
    public function destroy(AiProvider $provider)
    {
        if ($provider->connections()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete provider with existing connections');
        }

        $provider->delete();

        return redirect()->route('admin.ai-connections.providers.index')
            ->with('success', 'Provider deleted successfully');
    }
}

