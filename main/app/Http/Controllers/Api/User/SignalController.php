<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\DashboardSignal;
use App\Models\Signal;
use App\Services\SignalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group User APIs
 * Signal endpoints for users
 */
class SignalController extends Controller
{
    protected SignalService $signalService;

    public function __construct(SignalService $signalService)
    {
        $this->signalService = $signalService;
    }

    /**
     * List Signals
     * 
     * Get all signals available to the user
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['search', 'market_id', 'currency_pair_id', 'time_frame_id']);
        $result = $this->signalService->allSignals($params);

        return response()->json([
            'success' => $result['type'] === 'success',
            'data' => $result['data']['signals'] ?? null,
            'message' => $result['message'] ?? ''
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
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $result = $this->signalService->details($id);

        if ($result['type'] === 'error') {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], $result['code'] ?? 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']['signal']
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
     */
    public function dashboard(Request $request): JsonResponse
    {
        $signals = DashboardSignal::where('user_id', $request->user()->id)
            ->latest()
            ->with(['signal.market', 'signal.pair', 'signal.time', 'user'])
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $signals
        ]);
    }
}
