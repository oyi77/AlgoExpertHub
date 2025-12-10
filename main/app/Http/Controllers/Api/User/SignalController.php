<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\DashboardSignal;
use App\Models\Signal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group User APIs
 * Signal endpoints for users
 */
class SignalController extends Controller
{
    /**
     * List Signals
     * 
     * Get all signals available to the user
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @queryParam search string Search by signal ID or title. Example: EUR/USD
     * @queryParam market_id integer Filter by market ID. Example: 1
     * @queryParam currency_pair_id integer Filter by currency pair ID. Example: 1
     * @queryParam time_frame_id integer Filter by timeframe ID. Example: 1
     * @queryParam page integer Page number. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1234567,
     *         "title": "EUR/USD Buy Signal",
     *         "direction": "buy",
     *         "open_price": "1.1000",
     *         "sl": "1.0950",
     *         "tp": "1.1100",
     *         "published_date": "2023-01-01T00:00:00.000000Z",
     *         "pair": {...},
     *         "time": {...},
     *         "market": {...}
     *       }
     *     ]
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $dashboardSignal = DashboardSignal::where('user_id', $request->user()->id)->pluck('signal_id');

        $signals = Signal::when($request->search, function ($item) use ($request) {
            $item->where(function ($item) use ($request) {
                $item->where('id', $request->search)
                    ->orWhere('title', 'LIKE', '%' . $request->search . '%');
            });
        })
        ->when($request->market_id, function ($query) use ($request) {
            $query->where('market_id', $request->market_id);
        })
        ->when($request->currency_pair_id, function ($query) use ($request) {
            $query->where('currency_pair_id', $request->currency_pair_id);
        })
        ->when($request->time_frame_id, function ($query) use ($request) {
            $query->where('time_frame_id', $request->time_frame_id);
        })
        ->whereIn('id', $dashboardSignal)
        ->where('is_published', 1)
        ->latest()
        ->with('plans', 'pair', 'time', 'market')
        ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $signals
        ]);
    }

    /**
     * Get Signal Details
     * 
     * Get detailed information about a specific signal
     * 
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Signal ID. Example: 1234567
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1234567,
     *     "title": "EUR/USD Buy Signal",
     *     "description": "Signal description...",
     *     "direction": "buy",
     *     "open_price": "1.1000",
     *     "sl": "1.0950",
     *     "tp": "1.1100",
     *     "published_date": "2023-01-01T00:00:00.000000Z",
     *     "image": "path/to/image.jpg",
     *     "pair": {...},
     *     "time": {...},
     *     "market": {...}
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Signal not found"
     * }
     */
    public function show($id, Request $request): JsonResponse
    {
        $dashboardSignal = DashboardSignal::where('user_id', $request->user()->id)->pluck('signal_id');

        $signal = Signal::whereIn('id', $dashboardSignal)
            ->where('is_published', 1)
            ->with('plans', 'pair', 'time', 'market')
            ->find($id);

        if (!$signal) {
            return response()->json([
                'success' => false,
                'message' => 'Signal not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $signal
        ]);
    }

    /**
     * Get Dashboard Signals
     * 
     * Get signals displayed on user dashboard
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @queryParam page integer Page number. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1234567,
     *         "signal": {
     *           "id": 1234567,
     *           "title": "EUR/USD Buy Signal",
     *           "direction": "buy"
     *         }
     *       }
     *     ]
     *   }
     * }
     */
    public function dashboard(Request $request): JsonResponse
    {
        $signals = DashboardSignal::where('user_id', $request->user()->id)
            ->latest()
            ->with('signal.market', 'signal.pair', 'signal.time', 'user')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $signals
        ]);
    }
}
