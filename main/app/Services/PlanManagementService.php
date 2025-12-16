<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Contracts\ServiceInterface;
use Illuminate\Support\Facades\Storage;

class PlanManagementService extends BaseService implements ServiceInterface
{
    /**
     * Create a new plan
     */
    public function create(array $data): array
    {
        return $this->executeInTransaction(function () use ($data) {
            $this->logOperation('create_plan', ['name' => $data['name'] ?? null]);
            
            // Handle image upload if provided
            if (isset($data['image']) && $data['image']) {
                $data['image'] = $this->handleImageUpload($data['image']);
            }
            
            $plan = Plan::create($data);
            
            // Clear plans cache
            $this->invalidateCache(['plans']);
            
            return $this->successResponse('Plan created successfully', [
                'plan' => $plan
            ]);
        });
    }

    /**
     * Update an existing plan
     */
    public function update(int $id, array $data): array
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $plan = Plan::findOrFail($id);
            
            $this->logOperation('update_plan', ['plan_id' => $id, 'name' => $plan->name]);
            
            // Handle image upload if provided
            if (isset($data['image']) && $data['image']) {
                // Delete old image if exists
                if ($plan->image) {
                    $this->deleteImage($plan->image);
                }
                $data['image'] = $this->handleImageUpload($data['image']);
            }
            
            $plan->update($data);
            
            // Clear plans cache
            $this->invalidateCache(['plans', "plan:{$id}"]);
            
            return $this->successResponse('Plan updated successfully', [
                'plan' => $plan->fresh()
            ]);
        });
    }

    /**
     * Delete a plan
     */
    public function delete(int $id): array
    {
        return $this->executeInTransaction(function () use ($id) {
            $plan = Plan::findOrFail($id);
            
            $this->logOperation('delete_plan', ['plan_id' => $id, 'name' => $plan->name]);
            
            // Check if plan has active subscriptions
            $activeSubscriptions = $plan->subscriptions()
                ->where('is_current', 1)
                ->where('end_date', '>', now())
                ->count();
            
            if ($activeSubscriptions > 0) {
                return $this->errorResponse(
                    'Cannot delete plan with active subscriptions',
                    ['active_subscriptions' => $activeSubscriptions]
                );
            }
            
            // Delete plan image if exists
            if ($plan->image) {
                $this->deleteImage($plan->image);
            }
            
            $plan->delete();
            
            // Clear plans cache
            $this->invalidateCache(['plans', "plan:{$id}"]);
            
            return $this->successResponse('Plan deleted successfully');
        });
    }

    /**
     * Find a plan by ID
     */
    public function find(int $id): array
    {
        try {
            $plan = $this->cacheResult("plan:{$id}", function () use ($id) {
                return Plan::with([
                    'signals' => function ($query) {
                        $query->where('is_published', 1)
                              ->latest()
                              ->limit(10);
                    },
                    'subscriptions' => function ($query) {
                        $query->where('is_current', 1)
                              ->with('user:id,username,email');
                    }
                ])->findOrFail($id);
            }, 1800); // 30 minutes cache
            
            return $this->successResponse('Plan found', ['plan' => $plan]);
        } catch (\Exception $e) {
            return $this->errorResponse('Plan not found', [], 404);
        }
    }

    /**
     * Get paginated list of plans
     */
    public function list(array $params = []): array
    {
        try {
            $pagination = $this->getPaginationParams($params);
            
            $query = Plan::withCount(['subscriptions', 'signals']);
            
            // Apply search filters
            if (!empty($params['search'])) {
                $search = $params['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Apply status filter
            if (isset($params['status'])) {
                $query->where('status', $params['status']);
            }
            
            // Apply plan type filter
            if (!empty($params['plan_type'])) {
                $query->where('plan_type', $params['plan_type']);
            }
            
            // Apply price range filter
            if (!empty($params['price_min'])) {
                $query->where('price', '>=', $params['price_min']);
            }
            
            if (!empty($params['price_max'])) {
                $query->where('price', '<=', $params['price_max']);
            }
            
            // Apply sorting
            $query->orderBy($pagination['sort_by'], $pagination['sort_order']);
            
            $plans = $query->paginate($pagination['per_page']);
            
            return $this->successResponse('Plans retrieved successfully', [
                'plans' => $plans->items(),
                'pagination' => [
                    'current_page' => $plans->currentPage(),
                    'last_page' => $plans->lastPage(),
                    'per_page' => $plans->perPage(),
                    'total' => $plans->total()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve plans');
        }
    }

    /**
     * Change plan status (activate/deactivate)
     */
    public function changeStatus(int $id, bool $status): array
    {
        return $this->executeInTransaction(function () use ($id, $status) {
            $plan = Plan::findOrFail($id);
            
            $this->logOperation('change_plan_status', [
                'plan_id' => $id,
                'old_status' => $plan->status,
                'new_status' => $status
            ]);
            
            $plan->update(['status' => $status]);
            
            // Clear plans cache
            $this->invalidateCache(['plans', "plan:{$id}"]);
            
            $statusText = $status ? 'activated' : 'deactivated';
            
            return $this->successResponse("Plan {$statusText} successfully", [
                'plan' => $plan->fresh()
            ]);
        });
    }

    /**
     * Get plan statistics
     */
    public function getStatistics(): array
    {
        try {
            $stats = $this->cacheResult('plan_statistics', function () {
                return [
                    'total_plans' => Plan::count(),
                    'active_plans' => Plan::where('status', 1)->count(),
                    'inactive_plans' => Plan::where('status', 0)->count(),
                    'limited_plans' => Plan::where('plan_type', 'limited')->count(),
                    'lifetime_plans' => Plan::where('plan_type', 'lifetime')->count(),
                    'total_subscriptions' => PlanSubscription::count(),
                    'active_subscriptions' => PlanSubscription::where('is_current', 1)
                        ->where('end_date', '>', now())
                        ->count(),
                    'expired_subscriptions' => PlanSubscription::where('end_date', '<', now())
                        ->count(),
                    'revenue_this_month' => $this->getMonthlyRevenue(),
                    'most_popular_plan' => $this->getMostPopularPlan()
                ];
            }, 600); // 10 minutes cache
            
            return $this->successResponse('Plan statistics retrieved successfully', $stats);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve plan statistics');
        }
    }

    /**
     * Assign signals to plan
     */
    public function assignSignals(int $planId, array $signalIds): array
    {
        return $this->executeInTransaction(function () use ($planId, $signalIds) {
            $plan = Plan::findOrFail($planId);
            
            $this->logOperation('assign_signals_to_plan', [
                'plan_id' => $planId,
                'signal_count' => count($signalIds)
            ]);
            
            // Sync signals with the plan
            $plan->signals()->sync($signalIds);
            
            // Clear plans cache
            $this->invalidateCache(['plans', "plan:{$planId}", 'signals']);
            
            return $this->successResponse('Signals assigned to plan successfully', [
                'plan' => $plan->fresh(['signals']),
                'assigned_signals' => count($signalIds)
            ]);
        });
    }

    /**
     * Get plans available for subscription
     */
    public function getAvailablePlans(): array
    {
        try {
            $plans = $this->cacheResult('available_plans', function () {
                return Plan::where('status', 1)
                    ->withCount(['subscriptions', 'signals'])
                    ->orderBy('price')
                    ->get();
            }, 3600); // 1 hour cache
            
            return $this->successResponse('Available plans retrieved successfully', [
                'plans' => $plans
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve available plans');
        }
    }

    /**
     * Handle image upload
     */
    protected function handleImageUpload($image): string
    {
        $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs('plans', $filename, 'public');
        
        return $path;
    }

    /**
     * Delete image file
     */
    protected function deleteImage(string $imagePath): void
    {
        if (Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
    }

    /**
     * Get monthly revenue
     */
    protected function getMonthlyRevenue(): float
    {
        return PlanSubscription::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->join('plans', 'plan_subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price');
    }

    /**
     * Get most popular plan
     */
    protected function getMostPopularPlan(): ?array
    {
        $plan = Plan::withCount(['subscriptions' => function ($query) {
            $query->where('created_at', '>=', now()->subMonth());
        }])
        ->orderBy('subscriptions_count', 'desc')
        ->first();
        
        return $plan ? [
            'id' => $plan->id,
            'name' => $plan->name,
            'subscriptions_count' => $plan->subscriptions_count
        ] : null;
    }
}