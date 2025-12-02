<?php

namespace Addons\TradingExecutionEngine\App\Http\Controllers\User;

use Addons\TradingExecutionEngine\App\Http\Controllers\Controller;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Services\ConnectionService;
use App\Helpers\Helper\Helper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConnectionController extends Controller
{
    protected ConnectionService $connectionService;

    public function __construct(ConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    /**
     * Check if user has permission to use auto trading.
     */
    protected function checkPermission(): bool
    {
        $user = auth()->user();
        $subscription = $user->currentplan()->where('is_current', 1)->first();
        
        if (!$subscription) {
            return false;
        }

        // Check if plan has auto_execution feature
        // This can be enhanced with a plan_features table
        // For now, allow if user has active subscription
        return $subscription->plan_expired_at > now();
    }

    /**
     * Display user connections.
     */
    public function index(Request $request): View
    {
        if (!$this->checkPermission()) {
            abort(403, 'Auto trading is not available for your plan');
        }

        $data['title'] = 'My Trading Connections';

        $user = auth()->user();
        
        $query = ExecutionConnection::userOwned()
            ->where('user_id', $user->id)
            ->with(['user']);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $connections = $query->latest()->paginate(Helper::pagination());

        $data['connections'] = $connections;

        return view('trading-execution-engine::user.connections.index', $data);
    }

    /**
     * Show form to create a new connection.
     */
    public function create(): View
    {
        if (!$this->checkPermission()) {
            abort(403, 'Auto trading is not available for your plan');
        }

        $data['title'] = 'Create Connection';
        $data['types'] = ['crypto' => 'Cryptocurrency Exchange', 'fx' => 'Forex Broker (MT4/MT5)'];
        
        return view('trading-execution-engine::user.connections.create', $data);
    }

    /**
     * Store a new connection.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!$this->checkPermission()) {
            abort(403, 'Auto trading is not available for your plan');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:crypto,fx',
            'exchange_name' => 'required|string|max:255',
            'credentials' => 'required|string',
        ]);

        $user = auth()->user();

        // Parse credentials JSON
        $credentials = json_decode($request->credentials, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid JSON format for credentials');
        }

        $data = $request->only(['name', 'type', 'exchange_name', 'settings']);
        $data['credentials'] = $credentials;
        $data['user_id'] = $user->id;
        $data['is_admin_owned'] = false;
        $data['status'] = 'testing';

        $connection = $this->connectionService->create($data);

        // Test connection
        $test = $this->connectionService->testConnection($connection);

        if ($test['success']) {
            return redirect()->route('user.execution-connections.index')
                ->with('success', 'Connection created and tested successfully');
        }

        return redirect()->route('user.execution-connections.edit', $connection->id)
            ->with('warning', 'Connection created but test failed: ' . ($test['message'] ?? 'Unknown error'));
    }

    /**
     * Show form to edit a connection.
     */
    public function edit(int $id): View
    {
        if (!$this->checkPermission()) {
            abort(403, 'Auto trading is not available for your plan');
        }

        $user = auth()->user();
        
        $connection = ExecutionConnection::userOwned()
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $data['title'] = 'Edit Connection';
        $data['connection'] = $connection;
        $data['types'] = ['crypto' => 'Cryptocurrency Exchange', 'fx' => 'Forex Broker (MT4/MT5)'];

        return view('trading-execution-engine::user.connections.edit', $data);
    }

    /**
     * Update a connection.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        if (!$this->checkPermission()) {
            abort(403, 'Auto trading is not available for your plan');
        }

        $user = auth()->user();
        
        $connection = ExecutionConnection::userOwned()
            ->where('user_id', $user->id)
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

        return redirect()->route('user.execution-connections.index')
            ->with('success', 'Connection updated successfully');
    }

    /**
     * Delete a connection.
     */
    public function destroy(int $id): RedirectResponse
    {
        if (!$this->checkPermission()) {
            abort(403, 'Auto trading is not available for your plan');
        }

        $user = auth()->user();
        
        $connection = ExecutionConnection::userOwned()
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $this->connectionService->delete($connection);

        return redirect()->route('user.execution-connections.index')
            ->with('success', 'Connection deleted successfully');
    }

    /**
     * Test a connection.
     */
    public function test(int $id): RedirectResponse
    {
        if (!$this->checkPermission()) {
            abort(403, 'Auto trading is not available for your plan');
        }

        $user = auth()->user();
        
        $connection = ExecutionConnection::userOwned()
            ->where('user_id', $user->id)
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
        if (!$this->checkPermission()) {
            abort(403, 'Auto trading is not available for your plan');
        }

        $user = auth()->user();
        
        $connection = ExecutionConnection::userOwned()
            ->where('user_id', $user->id)
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
        if (!$this->checkPermission()) {
            abort(403, 'Auto trading is not available for your plan');
        }

        $user = auth()->user();
        
        $connection = ExecutionConnection::userOwned()
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $this->connectionService->deactivate($connection);

        return redirect()->back()->with('success', 'Connection deactivated successfully');
    }
}

