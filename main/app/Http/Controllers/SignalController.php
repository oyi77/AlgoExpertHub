<?php

namespace App\Http\Controllers;

use App\Helpers\Helper\Helper;
use App\Models\DashboardSignal;
use App\Models\Signal;
use Illuminate\Http\Request;

class SignalController extends Controller
{
    public function allSignals(Request $request)
    {
        $data['title'] = 'All Signals';

        $user = auth()->user();
        
        // Get user's current active plan
        $currentPlan = $user->currentplan()->first();
        
        if ($currentPlan) {
            // Get all published signals from user's active plan
            $data['signals'] = Signal::where('is_published', 1)
                ->when($request->search, function ($item) use ($request) {
                    $item->where(function ($item) use ($request) {
                        $item->where('id', $request->search)
                            ->orWhere('title', 'LIKE', '%' . $request->search . '%');
                    });
                })
                ->whereHas('plans', function ($query) use ($currentPlan) {
                    $query->where('plans.id', $currentPlan->plan_id);
                })
                ->latest('published_date')
                ->with('plans', 'pair', 'time', 'market')
                ->paginate(Helper::pagination());
        } else {
            // No active plan, show empty collection
            $data['signals'] = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]), 
                0, 
                Helper::pagination(), 
                1
            );
        }

        return view(Helper::themeView('user.signals'))->with($data);
    }

    public function details($id)
    {
        $data['signal'] = Signal::findOrFail($id);

        $data['title'] = 'Signal Description';

        return view(Helper::themeView('user.signal_details'))->with($data);
    }
}
