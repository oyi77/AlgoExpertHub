<?php

namespace Addons\TradingExecutionEngine\App\Http\Controllers\Backend;

use Addons\TradingExecutionEngine\App\Http\Controllers\Controller;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Services\ConnectionService;
use Addons\TradingExecutionEngine\App\Services\ExchangeService;
use App\Helpers\Helper\Helper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConnectionController extends Controller
{
    protected ConnectionService $connectionService;
    protected ExchangeService $exchangeService;

    public function __construct(ConnectionService $connectionService, ExchangeService $exchangeService)
    {
        $this->connectionService = $connectionService;
        $this->exchangeService = $exchangeService;
    }

    /**
     * Display all connections (admin-owned).
     */
    public function index(Request $request): View
    {
        $data['title'] = 'Trading Connections';

        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            abort(403, 'Unauthorized');
        }
        
        $query = ExecutionConnection::adminOwned()
            ->where('admin_id', $admin->id)
            ->with(['admin']);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        try {
            $connections = $query->latest()->paginate(Helper::pagination());
        } catch (\Exception $e) {
            \Log::error('Failed to paginate connections', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);
            // Return empty paginator if table doesn't exist
            $connections = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                Helper::pagination(),
                1,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $data['connections'] = $connections;
        
        try {
            $baseQuery = ExecutionConnection::adminOwned()->where('admin_id', $admin->id);
            $data['stats'] = [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->active()->count(),
                'crypto' => (clone $baseQuery)->byType('crypto')->count(),
                'fx' => (clone $baseQuery)->byType('fx')->count(),
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to get connection stats', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);
            $data['stats'] = [
                'total' => 0,
                'active' => 0,
                'crypto' => 0,
                'fx' => 0,
            ];
        }

        return view('trading-execution-engine::backend.connections.index', $data);
    }

    /**
     * Show a connection.
     */
    public function show(int $id): View
    {
        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            abort(403, 'Unauthorized');
        }
        
        $connection = ExecutionConnection::adminOwned()
            ->where('admin_id', $admin->id)
            ->with(['admin'])
            ->findOrFail($id);

        $data['title'] = 'Connection Details';
        $data['connection'] = $connection;

        return view('trading-execution-engine::backend.connections.show', $data);
    }

    /**
     * Show form to create a new connection.
     */
    public function create(): View
    {
        $data['title'] = 'Create Connection';
        $data['types'] = ['crypto' => 'Cryptocurrency Exchange', 'fx' => 'Forex Broker (MT4/MT5)'];
        $data['cryptoExchanges'] = $this->exchangeService->getCryptoExchanges();
        $data['forexBrokers'] = $this->exchangeService->getForexBrokers();
        
        return view('trading-execution-engine::backend.connections.create', $data);
    }

    /**
     * Store a new connection.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:crypto,fx',
            'exchange_name' => 'required|string|max:255',
            'credentials' => 'required|string',
        ]);

        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            abort(403, 'Unauthorized');
        }

        // Parse credentials JSON
        $credentials = json_decode($request->credentials, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid JSON format for credentials');
        }

        $data = $request->only(['name', 'type', 'exchange_name', 'settings']);
        $data['credentials'] = $credentials;
        $data['admin_id'] = $admin->id;
        $data['is_admin_owned'] = true;
        $data['status'] = 'testing';

        $connection = $this->connectionService->create($data);

        // Test connection
        $test = $this->connectionService->testConnection($connection);

        if ($test['success']) {
            return redirect()->route('admin.execution-connections.index')
                ->with('success', 'Connection created and tested successfully');
        }

        return redirect()->route('admin.execution-connections.edit', $connection->id)
            ->with('warning', 'Connection created but test failed: ' . ($test['message'] ?? 'Unknown error'));
    }

    /**
     * Show form to edit a connection.
     */
    public function edit(int $id): View
    {
        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            abort(403, 'Unauthorized');
        }
        
        $connection = ExecutionConnection::adminOwned()
            ->where('admin_id', $admin->id)
            ->findOrFail($id);

        $data['title'] = 'Edit Connection';
        $data['connection'] = $connection;
        $data['types'] = ['crypto' => 'Cryptocurrency Exchange', 'fx' => 'Forex Broker (MT4/MT5)'];
        $data['cryptoExchanges'] = $this->exchangeService->getCryptoExchanges();
        $data['forexBrokers'] = $this->exchangeService->getForexBrokers();

        return view('trading-execution-engine::backend.connections.edit', $data);
    }

    /**
     * Update a connection.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            abort(403, 'Unauthorized');
        }
        
        $connection = ExecutionConnection::adminOwned()
            ->where('admin_id', $admin->id)
            ->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:crypto,fx',
            'exchange_name' => 'required|string|max:255',
            'credentials' => 'required|string',
        ]);

        // Parse credentials JSON
        $credentials = json_decode($request->credentials, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid JSON format for credentials');
        }

        $data = $request->only(['name', 'type', 'exchange_name', 'settings']);
        $data['credentials'] = $credentials;
        
        $this->connectionService->update($connection, $data);

        // Test connection if requested
        if ($request->has('test_connection')) {
            $test = $this->connectionService->testConnection($connection);
            if ($test['success']) {
                return redirect()->back()->with('success', 'Connection updated and tested successfully');
            }
            return redirect()->back()->with('error', 'Connection test failed: ' . ($test['message'] ?? 'Unknown error'));
        }

        return redirect()->route('admin.execution-connections.index')
            ->with('success', 'Connection updated successfully');
    }

    /**
     * Delete a connection.
     */
    public function destroy(int $id): RedirectResponse
    {
        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            abort(403, 'Unauthorized');
        }
        
        $connection = ExecutionConnection::adminOwned()
            ->where('admin_id', $admin->id)
            ->findOrFail($id);

        $this->connectionService->delete($connection);

        return redirect()->route('admin.execution-connections.index')
            ->with('success', 'Connection deleted successfully');
    }

    /**
     * Test a connection (before saving - for create/edit forms).
     */
    public function testConnection(Request $request): \Illuminate\Http\JsonResponse
    {
        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'type' => 'required|in:crypto,fx',
            'exchange_name' => 'required|string',
            'credentials' => 'required|string',
        ]);

        try {
            // Parse credentials JSON
            $credentials = json_decode($request->credentials, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid JSON format for credentials: ' . json_last_error_msg()
                ], 400);
            }

            // Create temporary connection object for testing
            $connectionData = [
                'type' => $request->type,
                'exchange_name' => $request->exchange_name,
                'admin_id' => $admin->id,
                'is_admin_owned' => true,
            ];

            // Create a simple connection object for adapter initialization
            $mockConnection = new class($connectionData) {
                public $type;
                public $exchange_name;
                
                public function __construct($data) {
                    $this->type = $data['type'];
                    $this->exchange_name = $data['exchange_name'];
                }
            };

            // Get adapter instance
            $adapter = null;
            if ($request->type === 'crypto') {
                $adapter = new \Addons\TradingExecutionEngine\App\Adapters\CryptoExchangeAdapter($mockConnection);
            } elseif ($request->type === 'fx') {
                $adapter = new \Addons\TradingExecutionEngine\App\Adapters\FxBrokerAdapter($mockConnection);
            }
            
            if (!$adapter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unsupported exchange/broker type'
                ], 400);
            }
            
            // Test connection - adapter's testConnection accepts credentials array directly
            $test = $adapter->testConnection($credentials);

            return response()->json($test);
        } catch (\Exception $e) {
            \Log::error('Connection test failed', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test an existing connection.
     */
    public function test(int $id): RedirectResponse
    {
        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            abort(403, 'Unauthorized');
        }
        
        $connection = ExecutionConnection::adminOwned()
            ->where('admin_id', $admin->id)
            ->findOrFail($id);

        $test = $this->connectionService->testConnection($connection);

        if ($test['success']) {
            return redirect()->back()->with('success', 'Connection test successful');
        }

        return redirect()->back()->with('error', 'Connection test failed: ' . ($test['message'] ?? 'Unknown error'));
    }

    /**
     * Activate a connection.
     */
    public function activate(int $id): RedirectResponse
    {
        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            abort(403, 'Unauthorized');
        }
        
        $connection = ExecutionConnection::adminOwned()
            ->where('admin_id', $admin->id)
            ->findOrFail($id);

        if ($this->connectionService->activate($connection)) {
            return redirect()->back()->with('success', 'Connection activated successfully');
        }

        return redirect()->back()->with('error', 'Failed to activate connection. Please test it first.');
    }

    /**
     * Deactivate a connection.
     */
    public function deactivate(int $id): RedirectResponse
    {
        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            abort(403, 'Unauthorized');
        }
        
        $connection = ExecutionConnection::adminOwned()
            ->where('admin_id', $admin->id)
            ->findOrFail($id);

        $this->connectionService->deactivate($connection);

        return redirect()->back()->with('success', 'Connection deactivated successfully');
    }
}

