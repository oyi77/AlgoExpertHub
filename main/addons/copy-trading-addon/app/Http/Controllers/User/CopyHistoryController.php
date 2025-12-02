<?php

namespace Addons\CopyTrading\App\Http\Controllers\User;

use Addons\CopyTrading\App\Http\Controllers\Controller;
use Addons\CopyTrading\App\Models\CopyTradingExecution;
use App\Helpers\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CopyHistoryController extends Controller
{
    /**
     * Show copy trading history.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = CopyTradingExecution::byFollower($user->id)
            ->with(['trader', 'traderPosition', 'followerPosition', 'subscription']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->trader_id) {
            $query->where('trader_id', $request->trader_id);
        }

        $executions = $query->latest()->paginate(Helper::pagination());

        $data['title'] = 'Copy Trading History';
        $data['executions'] = $executions;

        return view('copy-trading::user.history.index', $data);
    }
}

