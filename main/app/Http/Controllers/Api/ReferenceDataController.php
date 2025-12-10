<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CurrencyPair;
use App\Models\Market;
use App\Models\TimeFrame;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Reference Data
 * Reference data endpoints (currency pairs, timeframes, markets)
 */
class ReferenceDataController extends Controller
{
    /**
     * List Currency Pairs
     * 
     * Get all active currency pairs
     * 
     * @param Request $request
     * @return JsonResponse
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "EUR/USD",
     *       "status": 1
     *     }
     *   ]
     * }
     */
    public function currencyPairs(Request $request): JsonResponse
    {
        $pairs = CurrencyPair::where('status', 1)->get();

        return response()->json([
            'success' => true,
            'data' => $pairs
        ]);
    }

    /**
     * List Timeframes
     * 
     * Get all active timeframes
     * 
     * @param Request $request
     * @return JsonResponse
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "1H",
     *       "status": 1
     *     }
     *   ]
     * }
     */
    public function timeframes(Request $request): JsonResponse
    {
        $timeframes = TimeFrame::where('status', 1)->get();

        return response()->json([
            'success' => true,
            'data' => $timeframes
        ]);
    }

    /**
     * List Markets
     * 
     * Get all active markets
     * 
     * @param Request $request
     * @return JsonResponse
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Forex",
     *       "status": 1
     *     }
     *   ]
     * }
     */
    public function markets(Request $request): JsonResponse
    {
        $markets = Market::where('status', 1)->get();

        return response()->json([
            'success' => true,
            'data' => $markets
        ]);
    }
}
