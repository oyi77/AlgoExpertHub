<?php

namespace Addons\AiConnectionAddon\App\Http\Controllers\Backend;

use Addons\AiConnectionAddon\App\Models\AiProvider;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Addons\AiConnectionAddon\App\Services\AiConnectionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ConnectionController extends Controller
{
    protected $connectionService;

    public function __construct(AiConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    /**
     * Display a listing of connections
     */
    public function index(Request $request)
    {
        $query = AiConnection::with('provider');

        // Filter by provider
        if ($request->has('provider_id') && $request->provider_id != '') {
            $query->where('provider_id', $request->provider_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $connections = $query->orderBy('priority')->paginate(20);
        $providers = AiProvider::all();

        return view('ai-connection-addon::backend.connections.index', compact('connections', 'providers'));
    }

    /**
     * Show the form for creating a new connection
     */
    public function create()
    {
        $providers = AiProvider::active()->get();

        return view('ai-connection-addon::backend.connections.create', compact('providers'));
    }

    /**
     * Store a newly created connection
     */
    public function store(Request $request)
    {
        $request->validate([
            'provider_id' => 'required|exists:ai_providers,id',
            'name' => 'required|string|max:255',
            'credentials' => 'required|array',
            'credentials.api_key' => 'required|string',
            'credentials.base_url' => 'nullable|url',
            'settings' => 'nullable|array',
            'priority' => 'required|integer|min:1',
            'rate_limit_per_minute' => 'nullable|integer|min:1',
            'rate_limit_per_day' => 'nullable|integer|min:1',
        ]);

        AiConnection::create([
            'provider_id' => $request->provider_id,
            'name' => $request->name,
            'credentials' => $request->credentials, // Will be encrypted automatically
            'settings' => $request->settings ?? [],
            'status' => 'active',
            'priority' => $request->priority,
            'rate_limit_per_minute' => $request->rate_limit_per_minute,
            'rate_limit_per_day' => $request->rate_limit_per_day,
        ]);

        return redirect()->route('admin.ai-connections.connections.index')
            ->with('success', 'Connection created successfully');
    }

    /**
     * Show the form for editing the specified connection
     */
    public function edit(AiConnection $connection)
    {
        $providers = AiProvider::all();
        
        return view('ai-connection-addon::backend.connections.edit', compact('connection', 'providers'));
    }

    /**
     * Update the specified connection
     */
    public function update(Request $request, AiConnection $connection)
    {
        $request->validate([
            'provider_id' => 'required|exists:ai_providers,id',
            'name' => 'required|string|max:255',
            'credentials' => 'sometimes|array',
            'credentials.api_key' => 'sometimes|string',
            'credentials.base_url' => 'nullable|url',
            'settings' => 'nullable|array',
            'priority' => 'required|integer|min:1',
            'rate_limit_per_minute' => 'nullable|integer|min:1',
            'rate_limit_per_day' => 'nullable|integer|min:1',
        ]);

        $data = [
            'provider_id' => $request->provider_id,
            'name' => $request->name,
            'settings' => $request->settings ?? [],
            'priority' => $request->priority,
            'rate_limit_per_minute' => $request->rate_limit_per_minute,
            'rate_limit_per_day' => $request->rate_limit_per_day,
        ];

        // Handle credentials update - merge with existing if only partial update
        if ($request->has('credentials')) {
            $newCredentials = $request->credentials;
            $existingCredentials = $connection->credentials;
            
            // Merge: keep existing values if new ones not provided
            $mergedCredentials = array_merge($existingCredentials, array_filter($newCredentials, function($value) {
                return !empty($value); // Only include non-empty values
            }));
            
            // If api_key is provided, use it; otherwise keep existing
            if (!empty($newCredentials['api_key'])) {
                $mergedCredentials['api_key'] = $newCredentials['api_key'];
            }
            
            // base_url can be set to empty string to clear it
            if (isset($newCredentials['base_url'])) {
                $mergedCredentials['base_url'] = !empty($newCredentials['base_url']) ? $newCredentials['base_url'] : null;
            }
            
            $data['credentials'] = $mergedCredentials;
        }

        $connection->update($data);

        return redirect()->route('admin.ai-connections.connections.index')
            ->with('success', 'Connection updated successfully');
    }

    /**
     * Remove the specified connection
     */
    public function destroy(AiConnection $connection)
    {
        // Check if this is the default connection for provider
        if ($connection->provider->default_connection_id == $connection->id) {
            $connection->provider->update(['default_connection_id' => null]);
        }

        $connection->delete();

        return redirect()->route('admin.ai-connections.connections.index')
            ->with('success', 'Connection deleted successfully');
    }

    /**
     * Test the specified connection
     */
    public function test(AiConnection $connection)
    {
        $result = $this->connectionService->testConnection($connection->id);

        return response()->json($result);
    }

    /**
     * Toggle connection status
     */
    public function toggleStatus(AiConnection $connection)
    {
        $newStatus = $connection->status === 'active' ? 'inactive' : 'active';
        $connection->update(['status' => $newStatus]);

        return redirect()->back()
            ->with('success', "Connection {$newStatus}");
    }
}

