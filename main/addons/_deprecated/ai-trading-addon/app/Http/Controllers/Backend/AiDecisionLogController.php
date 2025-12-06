<?php

namespace Addons\AiTradingAddon\App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Signal;

class AiDecisionLogController extends Controller
{
    public function index(Request $request)
    {
        // Check if Multi-Channel Addon is available
        if (!class_exists(\Addons\MultiChannelSignalAddon\App\Models\ChannelMessage::class)) {
            $title = 'AI Decision Logs';
            return view('ai-trading-addon::backend.ai-decision-logs.index', [
                'logs' => collect([])->paginate(20),
                'stats' => [
                    'total' => 0,
                    'filter_pass' => 0,
                    'filter_fail' => 0,
                    'ai_accept' => 0,
                    'ai_reject' => 0,
                ],
                'title' => $title
            ]);
        }

        $channelMessageClass = \Addons\MultiChannelSignalAddon\App\Models\ChannelMessage::class;
        $query = $channelMessageClass::whereNotNull('signal_id')
            ->whereNotNull('parsed_data')
            ->orderBy('created_at', 'desc');

        // Filter by date range
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by filter result - use raw JSON for better performance
        if ($request->filter_result) {
            $passValue = $request->filter_result === 'pass' ? 'true' : 'false';
            $query->whereRaw("JSON_EXTRACT(parsed_data, '$.filter_evaluation.pass') = ?", [$passValue]);
        }

        // Filter by AI decision - use raw JSON for better performance
        if ($request->ai_decision) {
            $executeValue = $request->ai_decision === 'execute' ? 'true' : 'false';
            $query->whereRaw("JSON_EXTRACT(parsed_data, '$.ai_evaluation.execute') = ?", [$executeValue]);
        }

        // Optimize: Limit stats calculation to avoid timeout
        try {
            $logs = $query->with(['signal:id,title', 'channelSource:id,name'])
                ->select('id', 'signal_id', 'channel_source_id', 'parsed_data', 'created_at')
                ->paginate(20);
        } catch (\Exception $e) {
            \Log::error('Error loading decision logs', ['error' => $e->getMessage()]);
            $logs = collect([])->paginate(20);
        }

        // Optimize stats: Use try-catch and limit calculation
        try {
            $stats = [
                'total' => $channelMessageClass::whereNotNull('signal_id')->count(),
                'filter_pass' => $channelMessageClass::whereNotNull('signal_id')
                    ->whereNotNull('parsed_data')
                    ->whereRaw("JSON_EXTRACT(parsed_data, '$.filter_evaluation.pass') = true")
                    ->count(),
                'filter_fail' => $channelMessageClass::whereNotNull('signal_id')
                    ->whereNotNull('parsed_data')
                    ->whereRaw("JSON_EXTRACT(parsed_data, '$.filter_evaluation.pass') = false")
                    ->count(),
                'ai_accept' => $channelMessageClass::whereNotNull('signal_id')
                    ->whereNotNull('parsed_data')
                    ->whereRaw("JSON_EXTRACT(parsed_data, '$.ai_evaluation.execute') = true")
                    ->count(),
                'ai_reject' => $channelMessageClass::whereNotNull('signal_id')
                    ->whereNotNull('parsed_data')
                    ->whereRaw("JSON_EXTRACT(parsed_data, '$.ai_evaluation.execute') = false")
                    ->count(),
            ];
        } catch (\Exception $e) {
            \Log::error('Error calculating stats', ['error' => $e->getMessage()]);
            $stats = [
                'total' => 0,
                'filter_pass' => 0,
                'filter_fail' => 0,
                'ai_accept' => 0,
                'ai_reject' => 0,
            ];
        }

        $title = 'AI Decision Logs';

        return view('ai-trading-addon::backend.ai-decision-logs.index', compact('logs', 'stats', 'title'));
    }

    public function show($id)
    {
        // Check if Multi-Channel Addon is available
        if (!class_exists(\Addons\MultiChannelSignalAddon\App\Models\ChannelMessage::class)) {
            abort(404, 'Multi-Channel Signal Addon is not available');
        }

        $channelMessageClass = \Addons\MultiChannelSignalAddon\App\Models\ChannelMessage::class;
        $channelMessage = $channelMessageClass::with(['signal', 'channelSource'])->findOrFail($id);
        
        $filterEvaluation = $channelMessage->parsed_data['filter_evaluation'] ?? null;
        $aiEvaluation = $channelMessage->parsed_data['ai_evaluation'] ?? null;
        $title = 'AI Decision Log Details';

        return view('ai-trading-addon::backend.ai-decision-logs.show', compact('channelMessage', 'filterEvaluation', 'aiEvaluation', 'title'));
    }
}

