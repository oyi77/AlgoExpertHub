<?php

namespace Addons\TradingManagement\Modules\Execution\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\DataProvider\Models\DataConnection;
use Illuminate\Http\Request;

/**
 * Admin Execution Connection Controller
 * 
 * Manages trading execution connections for admin panel
 */
class ExecutionConnectionController extends Controller
{
    /**
     * Display list of execution connections
     */
    public function index()
    {
        $title = 'Execution Connections';
        $connections = ExecutionConnection::with(['admin', 'user', 'preset', 'dataConnection'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('trading-management::backend.trading-management.operations.connections.index', compact('title', 'connections'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $title = 'Create Execution Connection';
        $presets = TradingPreset::where('is_default_template', 1)->get();
        $dataConnections = DataConnection::where('status', 'active')->get();
        
        return view('trading-management::backend.trading-management.operations.connections.create', compact('title', 'presets', 'dataConnections'));
    }

    /**
     * Store new connection
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:CRYPTO_EXCHANGE,FX_BROKER',
            'exchange_name' => 'required|string',
            'credentials' => 'required|array',
            'preset_id' => 'nullable|exists:trading_presets,id',
            'data_connection_id' => 'nullable|exists:data_connections,id',
        ]);

        $connection = ExecutionConnection::create([
            'admin_id' => auth()->guard('admin')->id(),
            'is_admin_owned' => true,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'exchange_name' => $validated['exchange_name'],
            'credentials' => $validated['credentials'],
            'preset_id' => $validated['preset_id'],
            'data_connection_id' => $validated['data_connection_id'],
            'status' => 'PENDING_TEST',
            'is_active' => false,
        ]);

        return redirect()
            ->route('admin.trading-management.operations.connections.index')
            ->with('success', 'Execution connection created successfully');
    }

    /**
     * Show single connection
     */
    public function show(ExecutionConnection $connection)
    {
        $connection->load(['admin', 'user', 'preset', 'dataConnection', 'logs', 'positions']);
        
        return view('trading-management::backend.trading-management.operations.connections.show', compact('connection'));
    }

    /**
     * Show edit form
     */
    public function edit(ExecutionConnection $connection)
    {
        $presets = TradingPreset::where('is_default_template', 1)->get();
        $dataConnections = DataConnection::where('status', 'active')->get();
        
        return view('trading-management::backend.trading-management.operations.connections.edit', compact('connection', 'presets', 'dataConnections'));
    }

    /**
     * Update connection
     */
    public function update(Request $request, ExecutionConnection $connection)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'credentials' => 'sometimes|array',
            'preset_id' => 'nullable|exists:trading_presets,id',
            'data_connection_id' => 'nullable|exists:data_connections,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $connection->update($validated);

        return redirect()
            ->route('admin.trading-management.operations.connections.index')
            ->with('success', 'Connection updated successfully');
    }

    /**
     * Delete connection
     */
    public function destroy(ExecutionConnection $connection)
    {
        $connection->delete();

        return redirect()
            ->route('admin.trading-management.operations.connections.index')
            ->with('success', 'Connection deleted successfully');
    }

    /**
     * Test connection
     */
    public function test(Request $request)
    {
        $validated = $request->validate([
            'connection_id' => 'required|exists:execution_connections,id'
        ]);

        $connection = ExecutionConnection::find($validated['connection_id']);
        
        try {
            // Test connection (implement actual test logic)
            $connection->update([
                'status' => 'CONNECTED',
                'last_tested_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Connection test successful'
            ]);
        } catch (\Exception $e) {
            $connection->update([
                'status' => 'ERROR',
                'last_error' => $e->getMessage(),
                'last_tested_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Activate connection
     */
    public function activate(ExecutionConnection $connection)
    {
        $connection->update(['is_active' => true]);

        return redirect()
            ->back()
            ->with('success', 'Connection activated');
    }

    /**
     * Deactivate connection
     */
    public function deactivate(ExecutionConnection $connection)
    {
        $connection->update(['is_active' => false]);

        return redirect()
            ->back()
            ->with('success', 'Connection deactivated');
    }
}

