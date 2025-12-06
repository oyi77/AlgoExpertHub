<?php

namespace Addons\TradingExecutionEngine\App\Http\Controllers\Backend;

use Addons\TradingExecutionEngine\App\Http\Controllers\Controller;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Addons\TradingExecutionEngine\App\Services\PositionService;
use App\Helpers\Helper\Helper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PositionController extends Controller
{
    protected PositionService $positionService;

    public function __construct(PositionService $positionService)
    {
        $this->positionService = $positionService;
    }

    /**
     * Display open positions.
     */
    public function index(Request $request): View
    {
        $data['title'] = 'Open Positions';

        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            abort(403, 'Unauthorized');
        }
        
        try {
            $query = ExecutionPosition::open()
                ->with(['signal', 'connection'])
                ->whereHas('connection', function ($q) use ($admin) {
                    $q->adminOwned()->where('admin_id', $admin->id);
                });
        } catch (\Exception $e) {
            \Log::error('Failed to query open positions', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);
            $query = ExecutionPosition::whereRaw('1 = 0'); // Empty query
        }

        if ($request->connection_id) {
            $query->where('connection_id', $request->connection_id);
        }

        try {
            $positions = $query->latest()->paginate(Helper::pagination());
        } catch (\Exception $e) {
            \Log::error('Failed to paginate open positions', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);
            $positions = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                Helper::pagination(),
                1,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $data['positions'] = $positions;
        
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

        return view('trading-execution-engine::backend.positions.index', $data);
    }

    /**
     * Display closed positions.
     */
    public function closed(Request $request): View
    {
        $data['title'] = 'Closed Positions';

        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            abort(403, 'Unauthorized');
        }
        
        try {
            $query = ExecutionPosition::closed()
                ->with(['signal', 'connection'])
                ->whereHas('connection', function ($q) use ($admin) {
                    $q->adminOwned()->where('admin_id', $admin->id);
                });
        } catch (\Exception $e) {
            \Log::error('Failed to query closed positions', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);
            $query = ExecutionPosition::whereRaw('1 = 0'); // Empty query
        }

        if ($request->connection_id) {
            $query->where('connection_id', $request->connection_id);
        }

        try {
            $positions = $query->orderBy('closed_at', 'desc')->paginate(Helper::pagination());
        } catch (\Exception $e) {
            \Log::error('Failed to paginate closed positions', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);
            $positions = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                Helper::pagination(),
                1,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $data['positions'] = $positions;
        
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

        return view('trading-execution-engine::backend.positions.closed', $data);
    }

    /**
     * Close a position manually.
     */
    public function close(int $id): RedirectResponse
    {
        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            abort(403, 'Unauthorized');
        }
        
        $position = ExecutionPosition::open()
            ->with('connection')
            ->whereHas('connection', function ($q) use ($admin) {
                $q->adminOwned()->where('admin_id', $admin->id);
            })
            ->findOrFail($id);

        if ($this->positionService->closePosition($position, 'manual')) {
            return redirect()->back()->with('success', 'Position closed successfully');
        }

        return redirect()->back()->with('error', 'Failed to close position');
    }
}

