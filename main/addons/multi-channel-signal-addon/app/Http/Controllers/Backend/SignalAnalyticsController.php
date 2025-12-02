<?php

namespace Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend;

use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Addons\MultiChannelSignalAddon\App\Services\ReportService;
use Addons\MultiChannelSignalAddon\App\Services\SignalAnalyticsService;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class SignalAnalyticsController extends Controller
{
    protected SignalAnalyticsService $analyticsService;
    protected ReportService $reportService;

    public function __construct(SignalAnalyticsService $analyticsService, ReportService $reportService)
    {
        $this->analyticsService = $analyticsService;
        $this->reportService = $reportService;
    }

    /**
     * Show analytics dashboard.
     */
    public function index(Request $request)
    {
        try {
            $channelSourceId = $request->get('channel_source_id');
            $planId = $request->get('plan_id');
            $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : now()->subDays(30);
            $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : now();

            $analytics = null;
            $dailyStats = [];

            if ($channelSourceId) {
                $analytics = $this->analyticsService->getChannelAnalytics($channelSourceId, $startDate, $endDate);
                $dailyStats = $this->analyticsService->getDailyStatistics($channelSourceId, $startDate->diffInDays($endDate));
            } elseif ($planId) {
                $analytics = $this->analyticsService->getPlanAnalytics($planId, $startDate, $endDate);
                $dailyStats = $this->analyticsService->getDailyStatistics(null, $startDate->diffInDays($endDate));
            } else {
                // Overall analytics
                $dailyStats = $this->analyticsService->getDailyStatistics(null, $startDate->diffInDays($endDate));
            }

            $channels = ChannelSource::active()->get();
            $plans = Plan::whereStatus(true)->get();
            $title = 'Signal Analytics & Reporting';

            return view('multi-channel-signal-addon::backend.analytics.index', compact(
                'analytics',
                'dailyStats',
                'channels',
                'plans',
                'channelSourceId',
                'planId',
                'startDate',
                'endDate',
                'title'
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("SignalAnalyticsController::index error: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->route('admin.channel-forwarding.index')
                ->with('error', 'An error occurred while loading analytics: ' . $e->getMessage());
        }
    }

    /**
     * Get channel analytics (AJAX).
     */
    public function channel(int $channelSourceId, Request $request)
    {
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : now()->subDays(30);
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : now();

        $analytics = $this->analyticsService->getChannelAnalytics($channelSourceId, $startDate, $endDate);
        $dailyStats = $this->analyticsService->getDailyStatistics($channelSourceId, $startDate->diffInDays($endDate));

        return response()->json([
            'analytics' => $analytics,
            'daily_stats' => $dailyStats,
        ]);
    }

    /**
     * Get plan analytics (AJAX).
     */
    public function plan(int $planId, Request $request)
    {
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : now()->subDays(30);
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : now();

        $analytics = $this->analyticsService->getPlanAnalytics($planId, $startDate, $endDate);

        return response()->json([
            'analytics' => $analytics,
        ]);
    }

    /**
     * Generate report.
     */
    public function report(Request $request)
    {
        $period = $request->get('period', 'daily'); // daily, weekly, monthly
        $date = $request->get('date') ? Carbon::parse($request->get('date')) : now();
        $channelSourceId = $request->get('channel_source_id');
        $planId = $request->get('plan_id');

        switch ($period) {
            case 'weekly':
                $report = $this->reportService->generateWeeklyReport($date, $channelSourceId, $planId);
                break;
            case 'monthly':
                $report = $this->reportService->generateMonthlyReport($date, $channelSourceId, $planId);
                break;
            default:
                $report = $this->reportService->generateDailyReport($date, $channelSourceId, $planId);
        }

        $channels = ChannelSource::active()->get();
        $plans = Plan::whereStatus(true)->get();
        $title = 'Signal Analytics Report';

        return view('multi-channel-signal-addon::backend.analytics.report', compact(
            'report',
            'period',
            'channels',
            'plans',
            'channelSourceId',
            'planId',
            'title'
        ));
    }

    /**
     * Export analytics report.
     */
    public function export(Request $request)
    {
        $period = $request->get('period', 'daily');
        $date = $request->get('date') ? Carbon::parse($request->get('date')) : now();
        $channelSourceId = $request->get('channel_source_id');
        $planId = $request->get('plan_id');
        $format = $request->get('format', 'csv');

        switch ($period) {
            case 'weekly':
                $report = $this->reportService->generateWeeklyReport($date, $channelSourceId, $planId);
                break;
            case 'monthly':
                $report = $this->reportService->generateMonthlyReport($date, $channelSourceId, $planId);
                break;
            default:
                $report = $this->reportService->generateDailyReport($date, $channelSourceId, $planId);
        }

        if ($format === 'csv') {
            $filepath = $this->reportService->exportToCsv($report);
            return Response::download($filepath)->deleteFileAfterSend(true);
        }

        return response()->json($report);
    }
}

