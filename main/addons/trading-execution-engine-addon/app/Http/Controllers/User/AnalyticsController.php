<?php

namespace Addons\TradingExecutionEngine\App\Http\Controllers\User;

use Addons\TradingExecutionEngine\App\Http\Controllers\Controller;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Addons\TradingExecutionEngine\App\Services\AnalyticsService;
use App\Helpers\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Check if user has permission.
     */
    protected function checkPermission(): bool
    {
        $user = auth()->user();
        $subscription = $user->currentplan()->where('is_current', 1)->first();
        return $subscription && $subscription->plan_expired_at > now();
    }

    /**
     * Display analytics dashboard.
     */
    public function index(Request $request): View
    {
        if (!$this->checkPermission()) {
            abort(403, 'Auto trading is not available for your plan');
        }

        $data['title'] = 'My Trading Analytics';

        $user = auth()->user();
        
        $connectionId = $request->connection_id;
        $days = $request->days ?? 30;

        $connections = ExecutionConnection::userOwned()
            ->where('user_id', $user->id)
            ->get();

        if ($connectionId) {
            $connection = $connections->find($connectionId);
            if ($connection) {
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
            }
        }

        $data['connections'] = $connections;
        $data['days'] = $days;

        return view('trading-execution-engine::user.analytics.index', $data);
    }
}

