<?php

namespace Addons\SmartRiskManagement\App\Http\Controllers\User;

use Addons\SmartRiskManagement\App\Http\Controllers\Controller;
use App\Helpers\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class SrmAdjustmentController extends Controller
{
    /**
     * Display a listing of SRM adjustments.
     */
    public function index(Request $request): View
    {
        $data['title'] = 'SRM Adjustments History';

        $user = Auth::user();
        $data['connections'] = collect([]);

        if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
            $data['adjustments'] = collect([]);
            return view('smart-risk-management::user.adjustments.index', $data);
        }

        $connectionIds = \Addons\TradingExecutionEngine\App\Models\ExecutionConnection::where('user_id', $user->id)
            ->pluck('id')
            ->toArray();

        // Get positions with SRM adjustments
        $query = \Addons\TradingExecutionEngine\App\Models\ExecutionPosition::whereIn('connection_id', $connectionIds)
            ->where(function ($q) {
                $q->whereNotNull('srm_adjusted_lot')
                    ->orWhereNotNull('srm_sl_buffer');
            });

        // Filter by connection
        if ($request->connection_id) {
            $query->where('connection_id', $request->connection_id);
        }

        // Filter by date range
        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $data['adjustments'] = $query->with(['signal', 'connection'])
            ->orderBy('created_at', 'desc')
            ->paginate(Helper::pagination());

        $data['connections'] = \Addons\TradingExecutionEngine\App\Models\ExecutionConnection::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        return view('smart-risk-management::user.adjustments.index', $data);
    }

    /**
     * Display the specified adjustment.
     */
    public function show(int $id): View
    {
        $data['title'] = 'Adjustment Details';

        $user = Auth::user();

        $position = \Addons\TradingExecutionEngine\App\Models\ExecutionPosition::with(['signal', 'connection'])
            ->whereHas('connection', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->findOrFail($id);

        $data['position'] = $position;
        $data['adjustment_reason'] = json_decode($position->srm_adjustment_reason ?? '{}', true);

        return view('smart-risk-management::user.adjustments.show', $data);
    }
}

