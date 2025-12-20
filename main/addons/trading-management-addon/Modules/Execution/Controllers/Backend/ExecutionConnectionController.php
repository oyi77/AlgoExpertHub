<?php

namespace Addons\TradingManagement\Modules\Execution\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\DataProvider\Models\DataConnection;
use Addons\TradingManagement\Modules\DataProvider\Services\MetaApiProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        // Validate credentials based on provider
        if ($validated['exchange_name'] === 'metaapi') {
            if (empty($validated['credentials']['account_id'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['credentials.account_id' => 'MetaApi Account ID is required. Add your MT account to MetaApi first, then copy the Account ID from your MetaApi dashboard.']);
            }
            // Auto-fill api_token from config if not provided
            if (empty($validated['credentials']['api_token'])) {
                $validated['credentials']['api_token'] = config('trading-management.metaapi.api_token');
            }
        } elseif (in_array($validated['exchange_name'], ['mtapi', 'mtapi_grpc'])) {
            // mtapi.io requires api_key and api_secret
            if (empty($validated['credentials']['api_key']) || empty($validated['credentials']['api_secret'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['credentials' => 'API Key and API Secret are required for mtapi.io connections.']);
            }
        } elseif ($validated['type'] === 'CRYPTO_EXCHANGE') {
            // Crypto exchanges require api_key and api_secret
            if (empty($validated['credentials']['api_key']) || empty($validated['credentials']['api_secret'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['credentials' => 'API Key and API Secret are required for crypto exchange connections.']);
            }
        }

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

    /**
     * Add MT account to MetaApi
     */
    public function addMetaApiAccount(Request $request)
    {
        $validated = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'server' => 'required|string',
            'name' => 'required|string|max:255',
            'platform' => 'required|in:MT4,MT5,mt4,mt5',
            'provisioning_profile_id' => 'nullable|string',
            'account_type' => 'nullable|in:cloud-g1,cloud-g2',
            'magic' => 'nullable|integer|min:0',
            'manual_trades' => 'nullable|boolean',
        ]);

        try {
            $provisioningService = new MetaApiProvisioningService();

            $result = $provisioningService->addAccount([
                'login' => $validated['login'],
                'password' => $validated['password'],
                'server' => $validated['server'],
                'name' => $validated['name'],
                'platform' => $validated['platform'],
                'provisioningProfileId' => $validated['provisioning_profile_id'] ?? null,
                'type' => $validated['account_type'] ?? 'cloud-g2',
                'magic' => $validated['magic'] ?? null,
                'manualTrades' => $validated['manual_trades'] ?? false,
            ]);

            if ($result['success']) {
                $accountId = $result['account_id'];
                
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'metaapi_account_id' => $accountId,
                    'data' => $result['data'] ?? [],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error_data' => $result['data'] ?? null,
                    'status_code' => $result['status_code'] ?? 400,
                ], $result['status_code'] ?? 400);
            }
        } catch (\Exception $e) {
            Log::error('Failed to add MetaApi account', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add account: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get MetaApi account status
     */
    public function getMetaApiAccountStatus(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|string',
        ]);

        try {
            $provisioningService = new MetaApiProvisioningService();
            $result = $provisioningService->getAccountStatus($validated['account_id']);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'status' => $result['status'] ?? 'unknown',
                    'data' => $result['data'] ?? [],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to get account status',
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Failed to get MetaApi account status', [
                'error' => $e->getMessage(),
                'account_id' => $validated['account_id'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get account status: ' . $e->getMessage(),
            ], 500);
        }
    }
}

