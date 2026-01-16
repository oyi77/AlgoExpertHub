<?php

namespace Addons\TradingManagement\Modules\AiAnalysis\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiDecision;
use Illuminate\Http\Request;

class AiDecisionLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AiDecision::query()->with(['signal']);
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('symbol')) {
            $query->where('symbol', 'like', '%' . $request->symbol . '%');
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('ai_connection_id')) {
            $query->where('ai_connection_id', 'like', '%' . $request->ai_connection_id . '%');
        }
        
        $decisions = $query->orderBy('created_at', 'desc')->paginate(50);
        
        return view('trading-management::backend.trading-management.strategy.ai-decisions.index', compact('decisions'));
    }
    
    public function export(Request $request)
    {
        $query = AiDecision::query();
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('symbol')) {
            $query->where('symbol', 'like', '%' . $request->symbol . '%');
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('ai_connection_id')) {
            $query->where('ai_connection_id', 'like', '%' . $request->ai_connection_id . '%');
        }
        
        $decisions = $query->orderBy('created_at', 'desc')->get();
        
        $headers = ['Timestamp', 'Symbol', 'Timeframe', 'Action', 'Confidence', 'Model Used', 'Reasoning', 'AI Connection ID'];
        $rows = [];
        
        foreach ($decisions as $d) {
            $rows[] = [
                $d->created_at,
                $d->symbol,
                $d->timeframe,
                strtoupper($d->action),
                $d->confidence,
                $d->model_used,
                $d->reasoning,
                $d->ai_connection_id,
            ];
        }
        
        $filename = 'ai-decisions-' . now()->format('Y-m-d-His') . '.csv';
        
        $responseHeaders = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        return response()->streamDownload(function () use ($headers, $rows) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, 200, $responseHeaders);
    }
}
