<?php

namespace Addons\TradingManagement\Modules\TradingBot\Controllers\User;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Services\BotAnalysisService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * BotAnalysisController
 * 
 * Dedicated controller for bot analysis features
 */
class BotAnalysisController extends Controller
{
    protected BotAnalysisService $analysisService;

    public function __construct(BotAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    /**
     * Get analysis metrics
     */
    public function metrics($id, Request $request): JsonResponse
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);

        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $metrics = $this->analysisService->calculateMetrics($bot, $filters);

        return response()->json([
            'success' => true,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Get performance chart data
     */
    public function chart($id, Request $request): JsonResponse
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);

        $period = $request->get('period', 'daily');
        $chartData = $this->analysisService->getPerformanceChart($bot, $period);

        return response()->json([
            'success' => true,
            'chart_data' => $chartData,
        ]);
    }

    /**
     * Compare multiple bots
     */
    public function compare(Request $request): JsonResponse
    {
        $botIds = $request->get('bot_ids', []);
        
        if (empty($botIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No bots selected for comparison',
            ], 400);
        }

        // Verify all bots belong to user
        $bots = TradingBot::forUser(auth()->id())
            ->whereIn('id', $botIds)
            ->get();

        if ($bots->count() !== count($botIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Some bots not found or not accessible',
            ], 403);
        }

        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $comparison = $this->analysisService->compareBots($botIds, $filters);

        return response()->json([
            'success' => true,
            'comparison' => $comparison,
        ]);
    }

    /**
     * Export analysis data
     */
    public function export($id, Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);
        $format = $request->get('format', 'csv');

        $data = $this->analysisService->exportAnalysis($bot, $format);

        $filename = 'bot_analysis_' . $bot->id . '_' . now()->format('Y-m-d') . '.' . $format;

        return response()->streamDownload(function () use ($data) {
            echo $data;
        }, $filename, [
            'Content-Type' => $format === 'json' ? 'application/json' : 'text/csv',
        ]);
    }
}

