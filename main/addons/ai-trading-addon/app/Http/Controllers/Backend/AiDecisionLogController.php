<?php

namespace Addons\AiTradingAddon\App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Addons\MultiChannelSignalAddon\App\Models\ChannelMessage;
use App\Models\Signal;

class AiDecisionLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ChannelMessage::whereNotNull('signal_id')
            ->whereNotNull('parsed_data')
            ->orderBy('created_at', 'desc');

        // Filter by date range
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by filter result
        if ($request->filter_result) {
            $query->whereJsonContains('parsed_data->filter_evaluation->pass', $request->filter_result === 'pass');
        }

        // Filter by AI decision
        if ($request->ai_decision) {
            $query->whereJsonContains('parsed_data->ai_evaluation->execute', $request->ai_decision === 'execute');
        }

        $logs = $query->with(['signal', 'channelSource'])
            ->paginate(20);

        $stats = [
            'total' => ChannelMessage::whereNotNull('signal_id')->count(),
            'filter_pass' => ChannelMessage::whereJsonContains('parsed_data->filter_evaluation->pass', true)->count(),
            'filter_fail' => ChannelMessage::whereJsonContains('parsed_data->filter_evaluation->pass', false)->count(),
            'ai_accept' => ChannelMessage::whereJsonContains('parsed_data->ai_evaluation->execute', true)->count(),
            'ai_reject' => ChannelMessage::whereJsonContains('parsed_data->ai_evaluation->execute', false)->count(),
        ];

        return view('ai-trading-addon::backend.ai-decision-logs.index', compact('logs', 'stats'));
    }

    public function show($id)
    {
        $channelMessage = ChannelMessage::with(['signal', 'channelSource'])->findOrFail($id);
        
        $filterEvaluation = $channelMessage->parsed_data['filter_evaluation'] ?? null;
        $aiEvaluation = $channelMessage->parsed_data['ai_evaluation'] ?? null;

        return view('ai-trading-addon::backend.ai-decision-logs.show', compact('channelMessage', 'filterEvaluation', 'aiEvaluation'));
    }
}

