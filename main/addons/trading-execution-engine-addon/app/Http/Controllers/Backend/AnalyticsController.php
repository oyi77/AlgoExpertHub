<?php

namespace Addons\TradingExecutionEngine\App\Http\Controllers\Backend;

use Addons\TradingExecutionEngine\App\Http\Controllers\Controller;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Addons\TradingExecutionEngine\App\Services\AnalyticsService;
use App\Helpers\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display analytics dashboard.
     */
    public function index(Request $request): View
    {
        $data['title'] = 'Trading Analytics';

        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            abort(403, 'Unauthorized');
        }
        
        $connectionId = $request->connection_id;
        $days = $request->days ?? 30;

        try {
            $connections = ExecutionConnection::adminOwned()
                ->where('admin_id', $admin->id)
                ->get();
        } catch (\Exception $e) {
            \Log::error('Failed to get connections for analytics', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);
            $connections = collect([]);
        }

        if ($connectionId) {
            $connection = $connections->find($connectionId);
            if ($connection) {
                try {
                    $data['connection'] = $connection;
                    $data['summary'] = $this->analyticsService->getAnalyticsSummary($connection, $days);
                    $data['open_positions'] = ExecutionPosition::open()
                        ->byConnection($connection->id)
                        ->get();
                    $data['recent_positions'] = ExecutionPosition::closed()
                        ->byConnection($connection->id)
                        ->orderBy('closed_at', 'desc')
                        ->limit(20)
                        ->get();
                } catch (\Exception $e) {
                    \Log::error('Failed to get analytics data', [
                        'connection_id' => $connectionId,
                        'error' => $e->getMessage()
                    ]);
                    $data['connection'] = $connection;
                    $data['summary'] = [
                        'total_trades' => 0,
                        'win_rate' => 0,
                        'total_pnl' => 0,
                        'profit_factor' => 0,
                    ];
                    $data['open_positions'] = collect([]);
                    $data['recent_positions'] = collect([]);
                }
            }
        }

        $data['connections'] = $connections;
        $data['days'] = $days;

        return view('trading-execution-engine::backend.analytics.index', $data);
    }

    /**
     * Compare multiple channels/connections.
     */
    public function compare(Request $request): View
    {
        $data['title'] = 'Channel Comparison';

        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            abort(403, 'Unauthorized');
        }

        $connectionIds = $request->get('connection_ids', []);
        $days = $request->get('days', 30);

        $connections = ExecutionConnection::adminOwned()
            ->where('admin_id', $admin->id)
            ->get();

        $comparison = [];
        if (!empty($connectionIds)) {
            $comparison = $this->analyticsService->compareChannels($connectionIds, $days);
        }

        $data['connections'] = $connections;
        $data['comparison'] = $comparison;
        $data['days'] = $days;

        return view('trading-execution-engine::backend.analytics.compare', $data);
    }

    /**
     * Export analytics to CSV.
     */
    public function exportCsv(Request $request): Response
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            abort(403, 'Unauthorized');
        }

        $connectionId = $request->get('connection_id');
        $days = $request->get('days', 30);

        $connection = ExecutionConnection::adminOwned()
            ->where('admin_id', $admin->id)
            ->findOrFail($connectionId);

        $csv = $this->analyticsService->exportToCsv($connection, $days);

        $filename = "analytics_{$connection->name}_{$days}days_" . date('Y-m-d') . ".csv";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export analytics to JSON.
     */
    public function exportJson(Request $request): Response
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            abort(403, 'Unauthorized');
        }

        $connectionId = $request->get('connection_id');
        $days = $request->get('days', 30);

        $connection = ExecutionConnection::adminOwned()
            ->where('admin_id', $admin->id)
            ->findOrFail($connectionId);

        $json = $this->analyticsService->exportToJson($connection, $days);

        $filename = "analytics_{$connection->name}_{$days}days_" . date('Y-m-d') . ".json";

        return response()->json($json, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}

