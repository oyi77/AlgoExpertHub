<?php

namespace Addons\TradingManagement\Modules\Execution\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\DataProvider\Models\DataConnection;
use Illuminate\Http\Request;

class ExecutionConnectionController extends Controller
{
    public function index()
    {
        $connections = ExecutionConnection::with('user', 'admin', 'preset', 'dataConnection')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('trading-management::backend.trading-management.operations.connections.index', compact('connections'));
    }

    public function create()
    {
        $presets = TradingPreset::enabled()->get();
        $dataConnections = DataConnection::active()->get();
        
        return view('trading-management::backend.trading-management.operations.connections.create', compact('presets', 'dataConnections'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:crypto,fx',
            'exchange_name' => 'required|string|max:255',
            'credentials' => 'required|array',
            'preset_id' => 'nullable|exists:trading_presets,id',
            'data_connection_id' => 'nullable|exists:data_connections,id',
            'is_admin_owned' => 'boolean',
        ]);

        $validated['admin_id'] = auth()->guard('admin')->id();
        $validated['is_admin_owned'] = $request->has('is_admin_owned');

        ExecutionConnection::create($validated);

        return redirect()->route('admin.trading-management.operations.connections.index')
            ->with('success', 'Execution connection created successfully');
    }

    public function edit(ExecutionConnection $executionConnection)
    {
        $presets = TradingPreset::enabled()->get();
        $dataConnections = DataConnection::active()->get();
        
        return view('trading-management::backend.trading-management.operations.connections.edit', [
            'connection' => $executionConnection,
            'presets' => $presets,
            'dataConnections' => $dataConnections,
        ]);
    }

    public function update(Request $request, ExecutionConnection $executionConnection)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:crypto,fx',
            'exchange_name' => 'required|string|max:255',
            'credentials' => 'required|array',
            'preset_id' => 'nullable|exists:trading_presets,id',
            'data_connection_id' => 'nullable|exists:data_connections,id',
        ]);

        $executionConnection->update($validated);

        return redirect()->route('admin.trading-management.operations.connections.index')
            ->with('success', 'Execution connection updated successfully');
    }

    public function destroy(ExecutionConnection $executionConnection)
    {
        $executionConnection->delete();

        return redirect()->route('admin.trading-management.operations.connections.index')
            ->with('success', 'Execution connection deleted successfully');
    }
}

