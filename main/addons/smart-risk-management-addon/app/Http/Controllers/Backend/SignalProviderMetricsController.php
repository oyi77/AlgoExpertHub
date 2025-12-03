<?php

namespace Addons\SmartRiskManagement\App\Http\Controllers\Backend;

use Addons\SmartRiskManagement\App\Http\Controllers\Controller;
use Addons\SmartRiskManagement\App\Models\SignalProviderMetrics;
use App\Helpers\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SignalProviderMetricsController extends Controller
{
    /**
     * Display a listing of signal provider metrics.
     */
    public function index(Request $request): View
    {
        $data['title'] = 'Signal Provider Metrics';

        $query = SignalProviderMetrics::query();

        // Filter by type
        if ($request->type) {
            $query->where('signal_provider_type', $request->type);
        }

        // Filter by date range
        if ($request->date_from) {
            $query->where('period_start', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('period_end', '<=', $request->date_to);
        }

        // Filter by performance score range
        if ($request->score_min) {
            $query->where('performance_score', '>=', $request->score_min);
        }
        if ($request->score_max) {
            $query->where('performance_score', '<=', $request->score_max);
        }

        // Search
        if ($request->search) {
            $query->where('signal_provider_id', 'like', '%' . $request->search . '%');
        }

        $data['metrics'] = $query->orderBy('period_end', 'desc')
            ->orderBy('performance_score', 'desc')
            ->paginate(Helper::pagination());

        $data['stats'] = [
            'total_providers' => SignalProviderMetrics::select('signal_provider_id', 'signal_provider_type')
                ->distinct()
                ->count(),
            'avg_performance_score' => SignalProviderMetrics::avg('performance_score') ?? 0,
            'total_signals' => SignalProviderMetrics::sum('total_signals') ?? 0,
        ];

        return view('smart-risk-management::backend.signal-providers.index', $data);
    }

    /**
     * Display the specified signal provider metrics.
     */
    public function show(string $id): View
    {
        $data['title'] = 'Signal Provider Details';

        $metric = SignalProviderMetrics::findOrFail($id);
        $data['metric'] = $metric;

        // Get historical metrics for chart
        $data['history'] = SignalProviderMetrics::where('signal_provider_id', $metric->signal_provider_id)
            ->where('signal_provider_type', $metric->signal_provider_type)
            ->orderBy('period_start', 'asc')
            ->limit(30)
            ->get();

        return view('smart-risk-management::backend.signal-providers.show', $data);
    }
}

