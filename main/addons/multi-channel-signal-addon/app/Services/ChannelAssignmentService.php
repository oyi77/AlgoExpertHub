<?php

namespace Addons\MultiChannelSignalAddon\App\Services;

use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChannelAssignmentService
{
    /**
     * Assign channel to specific users.
     *
     * @param ChannelSource $channel
     * @param array $userIds
     * @return void
     */
    public function assignToUsers(ChannelSource $channel, array $userIds): void
    {
        if (!$channel->isAdminOwned()) {
            throw new \InvalidArgumentException('Only admin-owned channels can have user assignments');
        }

        DB::transaction(function () use ($channel, $userIds) {
            // Remove existing user assignments if switching scope
            if ($channel->scope !== 'user' && $channel->scope !== null) {
                $channel->assignedUsers()->detach();
            }

            $channel->assignedUsers()->sync($userIds);
            $channel->update(['scope' => 'user']);

            Log::info("Channel {$channel->id} assigned to " . count($userIds) . " users");
        });
    }

    /**
     * Assign channel to specific plans.
     *
     * @param ChannelSource $channel
     * @param array $planIds
     * @return void
     */
    public function assignToPlans(ChannelSource $channel, array $planIds): void
    {
        if (!$channel->isAdminOwned()) {
            throw new \InvalidArgumentException('Only admin-owned channels can have plan assignments');
        }

        DB::transaction(function () use ($channel, $planIds) {
            // Remove existing plan assignments if switching scope
            if ($channel->scope !== 'plan' && $channel->scope !== null) {
                $channel->assignedPlans()->detach();
            }

            $channel->assignedPlans()->sync($planIds);
            $channel->update(['scope' => 'plan']);

            Log::info("Channel {$channel->id} assigned to " . count($planIds) . " plans");
        });
    }

    /**
     * Set global assignment for channel.
     *
     * @param ChannelSource $channel
     * @param bool $enabled
     * @return void
     */
    public function setGlobal(ChannelSource $channel, bool $enabled): void
    {
        if (!$channel->isAdminOwned()) {
            throw new \InvalidArgumentException('Only admin-owned channels can be set to global');
        }

        DB::transaction(function () use ($channel, $enabled) {
            if ($enabled) {
                // Clear specific assignments when setting global
                $channel->assignedUsers()->detach();
                $channel->assignedPlans()->detach();
                $channel->update(['scope' => 'global']);
                Log::info("Channel {$channel->id} set to global");
            } else {
                $channel->update(['scope' => null]);
                Log::info("Channel {$channel->id} global scope removed");
            }
        });
    }

    /**
     * Get all users who should receive signals from this channel.
     *
     * @param ChannelSource $channel
     * @return Collection
     */
    public function getRecipients(ChannelSource $channel): Collection
    {
        if (!$channel->isAdminOwned()) {
            return collect();
        }

        $recipients = collect();

        switch ($channel->scope) {
            case 'user':
                // Get users directly assigned
                $recipients = $channel->assignedUsers()->get();
                break;

            case 'plan':
                // Get all users subscribed to assigned plans
                $planIds = $channel->assignedPlans()->pluck('plans.id');
                $recipients = User::whereHas('currentplan', function ($query) use ($planIds) {
                    $query->whereIn('plan_id', $planIds)
                        ->where('is_current', 1)
                        ->where('plan_expired_at', '>', now());
                })->get();
                break;

            case 'global':
                // Get all active users
                $recipients = User::where('status', 1)->get();
                break;
        }

        return $recipients;
    }

    /**
     * Remove user assignment.
     *
     * @param ChannelSource $channel
     * @param int $userId
     * @return void
     */
    public function removeUserAssignment(ChannelSource $channel, int $userId): void
    {
        $channel->assignedUsers()->detach($userId);
        Log::info("User {$userId} removed from channel {$channel->id}");
    }

    /**
     * Remove plan assignment.
     *
     * @param ChannelSource $channel
     * @param int $planId
     * @return void
     */
    public function removePlanAssignment(ChannelSource $channel, int $planId): void
    {
        $channel->assignedPlans()->detach($planId);
        Log::info("Plan {$planId} removed from channel {$channel->id}");
    }

    /**
     * Clear global assignment.
     *
     * @param ChannelSource $channel
     * @return void
     */
    public function clearGlobal(ChannelSource $channel): void
    {
        $channel->update(['scope' => null]);
        Log::info("Global scope cleared for channel {$channel->id}");
    }

    /**
     * Get assignment summary for display.
     *
     * @param ChannelSource $channel
     * @return array
     */
    public function getAssignmentSummary(ChannelSource $channel): array
    {
        if (!$channel->isAdminOwned()) {
            return ['type' => 'user', 'count' => 1, 'label' => 'User-owned'];
        }

        switch ($channel->scope) {
            case 'user':
                $count = $channel->assignedUsers()->count();
                return ['type' => 'user', 'count' => $count, 'label' => "{$count} user(s)"];
            
            case 'plan':
                $count = $channel->assignedPlans()->count();
                return ['type' => 'plan', 'count' => $count, 'label' => "{$count} plan(s)"];
            
            case 'global':
                return ['type' => 'global', 'count' => null, 'label' => 'Global (all users)'];
            
            default:
                return ['type' => 'none', 'count' => 0, 'label' => 'Not assigned'];
        }
    }
}

