<?php

namespace Addons\AiConnectionAddon\App\Http\Controllers\Backend;

use Addons\AiConnectionAddon\App\Models\AiConnection;
use Addons\AiConnectionAddon\App\Models\AiConnectionUsage;
use Addons\AiConnectionAddon\App\Services\AiConnectionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UsageAnalyticsController extends Controller
{
    protected $connectionService;

    public function __construct(AiConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    /**
     * Display usage analytics dashboard
     */
    public function index(Request $request)
    {
        $days = $request->get('days', 30);
        
        // Overall statistics
        $totalCost = AiConnectionUsage::getTotalCost(null, $days);
        $totalTokens = AiConnectionUsage::getTotalTokens(null, $days);
        $usageByFeature = AiConnectionUsage::getUsageByFeature($days);
        $avgResponseTime = AiConnectionUsage::getAverageResponseTime(null, $days);

        // Top connections by usage
        $topConnections = AiConnection::withCount(['usageLogs' => function ($query) use ($days) {
            $query->where('created_at', '>=', now()->subDays($days));
        }])->orderBy('usage_logs_count', 'desc')->take(10)->get();

        // Recent errors
        $recentErrors = AiConnectionUsage::with('connection.provider')
            ->where('success', false)
            ->latest()
            ->take(20)
            ->get();

        // Daily usage for chart
        $dailyUsage = AiConnectionUsage::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(cost) as cost, SUM(tokens_used) as tokens')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('ai-connection-addon::backend.analytics.index', compact(
            'totalCost',
            'totalTokens',
            'usageByFeature',
            'avgResponseTime',
            'topConnections',
            'recentErrors',
            'dailyUsage',
            'days'
        ));
    }

    /**
     * Display analytics for specific connection
     */
    public function connection(Request $request, AiConnection $connection)
    {
        $days = $request->get('days', 30);

        $stats = $this->connectionService->getUsageStatistics($connection->id, $days);

        // Usage over time
        $dailyUsage = $connection->usageLogs()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(cost) as cost, SUM(tokens_used) as tokens')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Recent usage
        $recentUsage = $connection->recentUsage($days)->paginate(50);

        return view('ai-connection-addon::backend.analytics.connection', compact(
            'connection',
            'stats',
            'dailyUsage',
            'recentUsage',
            'days'
        ));
    }

    /**
     * Export usage data
     */
    public function export(Request $request)
    {
        $days = $request->get('days', 30);
        $connectionId = $request->get('connection_id');

        $query = AiConnectionUsage::with('connection.provider')
            ->where('created_at', '>=', now()->subDays($days));

        if ($connectionId) {
            $query->where('connection_id', $connectionId);
        }

        $usage = $query->get();

        // Generate CSV
        $filename = 'ai-usage-' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($usage) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Date',
                'Provider',
                'Connection',
                'Feature',
                'Tokens Used',
                'Cost',
                'Success',
                'Response Time (ms)',
                'Error Message',
            ]);

            // Data rows
            foreach ($usage as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->connection->provider->name ?? 'N/A',
                    $log->connection->name ?? 'N/A',
                    $log->feature,
                    $log->tokens_used,
                    $log->cost,
                    $log->success ? 'Yes' : 'No',
                    $log->response_time_ms ?? 'N/A',
                    $log->error_message ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

