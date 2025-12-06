<?php

namespace Addons\FilterStrategyAddon\App\Services;

use Addons\FilterStrategyAddon\App\Models\FilterStrategy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FilterStrategyService
{
    /**
     * Create a new filter strategy
     */
    public function create(array $data, ?User $user = null): FilterStrategy
    {
        try {
            DB::beginTransaction();

            if ($user) {
                $data['created_by_user_id'] = $user->id;
            }

            if (!isset($data['visibility'])) {
                $data['visibility'] = 'PRIVATE';
            }

            $strategy = FilterStrategy::create($data);

            DB::commit();

            Log::info("Filter strategy created", ['strategy_id' => $strategy->id, 'user_id' => $user?->id]);

            return $strategy;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create filter strategy", ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        }
    }

    /**
     * Update a filter strategy
     */
    public function update(FilterStrategy $strategy, array $data, ?User $user = null): FilterStrategy
    {
        if ($user && !$strategy->canEditBy($user->id)) {
            throw new \Exception('You do not have permission to edit this strategy.');
        }

        try {
            DB::beginTransaction();

            $strategy->update($data);

            DB::commit();

            Log::info("Filter strategy updated", ['strategy_id' => $strategy->id]);

            return $strategy->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update filter strategy", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Delete a filter strategy
     */
    public function delete(FilterStrategy $strategy, ?User $user = null): bool
    {
        if ($user && !$strategy->canEditBy($user->id)) {
            throw new \Exception('You do not have permission to delete this strategy.');
        }

        try {
            $strategy->delete();
            Log::info("Filter strategy deleted", ['strategy_id' => $strategy->id]);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete filter strategy", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Clone a filter strategy for a user
     */
    public function clone(FilterStrategy $strategy, User $user): FilterStrategy
    {
        if (!$strategy->isClonable() && !$strategy->canEditBy($user->id)) {
            throw new \Exception('This strategy cannot be cloned.');
        }

        return $strategy->cloneForUser($user->id);
    }
}

