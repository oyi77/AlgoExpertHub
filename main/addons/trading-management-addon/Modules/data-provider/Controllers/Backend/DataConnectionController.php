<?php

namespace Addons\TradingManagement\Modules\DataProvider\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\DataProvider\Models\DataConnection;
use Addons\TradingManagement\Modules\DataProvider\Services\DataConnectionService;
use Addons\TradingManagement\Modules\DataProvider\Services\AdapterFactory;
use Illuminate\Http\Request;

/**
 * Admin Data Connection Controller
 * 
 * Manages data connections for admin panel
 */
class DataConnectionController extends Controller
{
    protected DataConnectionService $connectionService;

    public function __construct(DataConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    /**
     * Display list of data connections
     */
    public function index()
    {
        $connections = DataConnection::with('admin', 'user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('trading-management::backend.trading-management.config.data-connections.index', compact('connections'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $supportedTypes = AdapterFactory::getSupportedTypes();
        
        return view('trading-management::backend.trading-management.config.data-connections.create', compact('supportedTypes'));
    }

    /**
     * Store new connection
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:mtapi,ccxt_crypto,custom_api',
            'provider' => 'required|string|max:255',
            'credentials' => 'required|array',
            'credentials.api_key' => 'required_if:type,mtapi|string',
            'credentials.account_id' => 'required_if:type,mtapi|string',
            'config' => 'nullable|array',
            'settings' => 'nullable|array',
            'settings.symbols' => 'nullable|array',
            'settings.timeframes' => 'nullable|array',
            'is_admin_owned' => 'boolean',
        ]);

        // Add admin_id
        $validated['admin_id'] = auth()->guard('admin')->id();
        $validated['is_admin_owned'] = $request->has('is_admin_owned') && $request->is_admin_owned;

        $result = $this->connectionService->create($validated);

        return redirect()->route('admin.trading-management.config.data-connections.index')
            ->with($result['type'], $result['message']);
    }

    /**
     * Show edit form
     */
    public function edit(DataConnection $dataConnection)
    {
        $supportedTypes = AdapterFactory::getSupportedTypes();
        
        return view('trading-management::backend.trading-management.config.data-connections.edit', [
            'connection' => $dataConnection,
            'supportedTypes' => $supportedTypes,
        ]);
    }

    /**
     * Update connection
     */
    public function update(Request $request, DataConnection $dataConnection)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:mtapi,ccxt_crypto,custom_api',
            'provider' => 'required|string|max:255',
            'credentials' => 'required|array',
            'config' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        $result = $this->connectionService->update($dataConnection, $validated);

        return redirect()->route('admin.trading-management.config.data-connections.index')
            ->with($result['type'], $result['message']);
    }

    /**
     * Delete connection
     */
    public function destroy(DataConnection $dataConnection)
    {
        $result = $this->connectionService->delete($dataConnection);

        return redirect()->route('admin.trading-management.config.data-connections.index')
            ->with($result['type'], $result['message']);
    }

    /**
     * Test connection (AJAX)
     */
    public function test(Request $request)
    {
        $validated = $request->validate([
            'connection_id' => 'required|exists:data_connections,id',
        ]);

        $connection = DataConnection::findOrFail($validated['connection_id']);
        $result = $this->connectionService->test($connection);

        return response()->json($result);
    }

    /**
     * Activate connection
     */
    public function activate(DataConnection $dataConnection)
    {
        $result = $this->connectionService->activate($dataConnection);

        return redirect()->back()->with($result['type'], $result['message']);
    }

    /**
     * Deactivate connection
     */
    public function deactivate(DataConnection $dataConnection)
    {
        $result = $this->connectionService->deactivate($dataConnection);

        return redirect()->back()->with($result['type'], $result['message']);
    }

    /**
     * View market data for a connection
     */
    public function marketData(DataConnection $dataConnection, Request $request)
    {
        $symbol = $request->get('symbol', $dataConnection->getSymbolsFromSettings()[0] ?? 'EURUSD');
        $timeframe = $request->get('timeframe', 'H1');
        $limit = $request->get('limit', 100);

        $marketDataService = app(\Addons\TradingManagement\Modules\MarketData\Services\MarketDataService::class);
        $data = $marketDataService->getLatest($symbol, $timeframe, $limit);

        return view('trading-management::backend.trading-management.config.data-connections.market-data', [
            'connection' => $dataConnection,
            'marketData' => $data,
            'symbol' => $symbol,
            'timeframe' => $timeframe,
            'symbols' => $dataConnection->getSymbolsFromSettings(),
        ]);
    }

    /**
     * View connection logs
     */
    public function logs(DataConnection $dataConnection)
    {
        $logs = $dataConnection->logs()
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('trading-management::backend.trading-management.config.data-connections.logs', [
            'connection' => $dataConnection,
            'logs' => $logs,
        ]);
    }
}

