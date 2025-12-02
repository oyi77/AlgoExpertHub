<?php

namespace Addons\TradingExecutionEngine\App\Http\Controllers\Backend;

use Addons\TradingExecutionEngine\App\Http\Controllers\Controller;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionLog;
use App\Helpers\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExecutionController extends Controller
{
    /**
     * Display execution logs.
     */
    public function index(Request $request): View
    {
        $data['title'] = 'Execution Logs';

        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            abort(403, 'Unauthorized');
        }
        
        try {
            $query = ExecutionLog::with(['signal', 'connection', 'position'])
                ->whereHas('connection', function ($q) use ($admin) {
                    $q->adminOwned()->where('admin_id', $admin->id);
                });
        } catch (\Exception $e) {
            \Log::error('Failed to query execution logs', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);
            $query = ExecutionLog::whereRaw('1 = 0'); // Empty query
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->connection_id) {
            $query->where('connection_id', $request->connection_id);
        }

        try {
            $logs = $query->latest()->paginate(Helper::pagination());
        } catch (\Exception $e) {
            \Log::error('Failed to paginate execution logs', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);
            $logs = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                Helper::pagination(),
                1,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $data['logs'] = $logs;
        
        try {
            $data['connections'] = ExecutionConnection::adminOwned()
                ->where('admin_id', $admin->id)
                ->get();
        } catch (\Exception $e) {
            \Log::error('Failed to get connections', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);
            $data['connections'] = collect([]);
        }

        return view('trading-execution-engine::backend.executions.index', $data);
    }

    /**
     * Show execution log details.
     */
    public function show(int $id): View
    {
        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            abort(403, 'Unauthorized');
        }
        
        $log = ExecutionLog::with(['signal', 'connection', 'position'])
            ->whereHas('connection', function ($q) use ($admin) {
                $q->adminOwned()->where('admin_id', $admin->id);
            })
            ->findOrFail($id);

        $data['title'] = 'Execution Details';
        $data['log'] = $log;

        return view('trading-execution-engine::backend.executions.show', $data);
    }
}

