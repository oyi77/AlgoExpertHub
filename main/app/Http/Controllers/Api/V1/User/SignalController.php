<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\SignalResource;
use App\Models\Signal;
use App\Services\SignalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SignalController extends BaseApiController
{
    protected SignalService $signalService;

    public function __construct(SignalService $signalService)
    {
        $this->signalService = $signalService;
    }

    /**
     * Display a listing of signals for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $limit = $this->getPaginationLimit($request);
            [$sort, $direction] = $this->getSortParameters($request, 'published_date', 'desc');
            
            $allowedFilters = ['market_id', 'currency_pair_id', 'time_frame_id', 'direction', 'is_published'];
            $filters = $this->getFilterParameters($request, $allowedFilters);
            
            $search = $request->get('search');
            $searchFields = ['title', 'description'];

            // Get user's current plan signals
            $query = Signal::published()
                ->whereHas('plans.subscriptions', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->where('is_current', 1)
                      ->where('status', 'active');
                })
                ->with(['pair', 'time', 'market', 'plans']);

            // Apply filters
            $query = $this->applyFilters($query, $filters);
            
            // Apply search
            $query = $this->applySearch($query, $search, $searchFields);
            
            // Apply sorting
            $query->orderBy($sort, $direction);

            $signals = $query->paginate($limit);

            return $this->paginatedResponse(
                SignalResource::collection($signals),
                'Signals retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified signal.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            $signal = Signal::with(['pair', 'time', 'market', 'plans'])
                ->whereHas('plans.subscriptions', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->where('is_current', 1)
                      ->where('status', 'active');
                })
                ->find($id);

            if (!$signal) {
                return $this->notFoundResponse('Signal not found or not accessible');
            }

            return $this->successResponse(
                new SignalResource($signal),
                'Signal retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get signals for user dashboard.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $limit = min((int) $request->get('limit', 10), 20); // Smaller limit for dashboard

            $signals = Signal::published()
                ->whereHas('plans.subscriptions', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->where('is_current', 1)
                      ->where('status', 'active');
                })
                ->with(['pair', 'time', 'market'])
                ->orderBy('published_date', 'desc')
                ->limit($limit)
                ->get();

            return $this->successResponse(
                SignalResource::collection($signals),
                'Dashboard signals retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get signal statistics for the user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $stats = [
                'total_signals' => Signal::published()
                    ->whereHas('plans.subscriptions', function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                          ->where('is_current', 1)
                          ->where('status', 'active');
                    })
                    ->count(),
                    
                'signals_this_month' => Signal::published()
                    ->whereHas('plans.subscriptions', function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                          ->where('is_current', 1)
                          ->where('status', 'active');
                    })
                    ->whereMonth('published_date', now()->month)
                    ->whereYear('published_date', now()->year)
                    ->count(),
                    
                'signals_today' => Signal::published()
                    ->whereHas('plans.subscriptions', function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                          ->where('is_current', 1)
                          ->where('status', 'active');
                    })
                    ->whereDate('published_date', now()->toDateString())
                    ->count(),
                    
                'by_direction' => Signal::published()
                    ->whereHas('plans.subscriptions', function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                          ->where('is_current', 1)
                          ->where('status', 'active');
                    })
                    ->selectRaw('direction, COUNT(*) as count')
                    ->groupBy('direction')
                    ->pluck('count', 'direction')
                    ->toArray(),
                    
                'by_market' => Signal::published()
                    ->whereHas('plans.subscriptions', function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                          ->where('is_current', 1)
                          ->where('status', 'active');
                    })
                    ->join('markets', 'signals.market_id', '=', 'markets.id')
                    ->selectRaw('markets.name as market, COUNT(*) as count')
                    ->groupBy('markets.name')
                    ->pluck('count', 'market')
                    ->toArray(),
            ];

            return $this->successResponse($stats, 'Signal statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}