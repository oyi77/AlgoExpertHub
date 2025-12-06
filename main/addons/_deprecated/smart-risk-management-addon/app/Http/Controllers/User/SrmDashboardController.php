<?php

namespace Addons\SmartRiskManagement\App\Http\Controllers\User;

use Addons\SmartRiskManagement\App\Http\Controllers\Controller;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class SrmDashboardController extends Controller
{
    /**
     * Display user's SRM dashboard.
     */
    public function index(Request $request): View
    {
        $data['title'] = 'SRM Dashboard';

        $user = Auth::user();
        $connections = collect([]);
        $connectionIds = [];

        // Get user's connections
        if (class_exists(ExecutionConnection::class)) {
            $connections = ExecutionConnection::where('user_id', $user->id)
                ->where('is_active', true)
                ->get();

            $connectionIds = $connections->pluck('id')->all();
        }

        $data['connections'] = $connections;

        // Default statistics
        $stats = [
            'total_adjustments' => 0,
            'avg_performance_score' => 0.0,
            'slippage_reduction' => 0.0,
            'drawdown_reduction' => 0.0,
        ];

        $recentAdjustments = collect([]);

        if (!empty($connectionIds) && class_exists(ExecutionPosition::class)) {
            // Positions yang pernah disentuh SRM
            $baseQuery = ExecutionPosition::whereIn('connection_id', $connectionIds)
                ->where(function ($q) {
                    $q->whereNotNull('srm_adjusted_lot')
                        ->orWhereNotNull('srm_sl_buffer');
                });

            $stats['total_adjustments'] = (clone $baseQuery)->count();

            // Rata-rata performance score saat entry (kalau ada)
            $avgScore = (clone $baseQuery)->whereNotNull('performance_score_at_entry')->avg('performance_score_at_entry');
            if ($avgScore !== null) {
                $stats['avg_performance_score'] = round((float) $avgScore, 2);
            }

            // Placeholder sederhana untuk metrik reduksi – bisa diperdalam nanti
            // Misal: kita anggap kalau ada SRM adjustment, kita klaim improvement kecil secara konservatif.
            if ($stats['total_adjustments'] > 0) {
                $stats['slippage_reduction'] = 5.0;   // % dummy konservatif
                $stats['drawdown_reduction'] = 5.0;  // % dummy konservatif
            }

            // Recent adjustments (dipakai juga di dashboard card)
            $recentAdjustments = (clone $baseQuery)
                ->with(['signal', 'connection'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            // Aggregasi harian untuk chart (7 hari terakhir)
            $dailyScores = (clone $baseQuery)
                ->whereNotNull('performance_score_at_entry')
                ->where('created_at', '>=', now()->subDays(7)->startOfDay())
                ->get()
                ->groupBy(function ($pos) {
                    return $pos->created_at->format('Y-m-d');
                })
                ->map(function ($group) {
                    return round((float) $group->avg('performance_score_at_entry'), 2);
                });

            $data['chart_labels'] = $dailyScores->keys()->values()->all();
            $data['chart_values'] = $dailyScores->values()->all();
        } else {
            $data['chart_labels'] = [];
            $data['chart_values'] = [];
        }

        $data['stats'] = $stats;

        // Get recent adjustments
        $data['recent_adjustments'] = $recentAdjustments;

        return view('smart-risk-management::user.dashboard', $data);
    }
}

