<?php

namespace Addons\TradingManagement\Modules\DataProvider\Services;

use Addons\TradingManagement\Modules\DataProvider\Models\MetaapiStream;
use Addons\TradingManagement\Modules\DataProvider\Models\StreamSubscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shared Stream Manager
 * 
 * Manages shared streams per symbol/timeframe
 * Coordinates stream lifecycle and tracks subscribers
 */
class SharedStreamManager
{
    /**
     * Get or create stream for account/symbol/timeframe
     */
    public function getOrCreateStream(string $accountId, string $symbol, string $timeframe): MetaapiStream
    {
        return MetaapiStream::firstOrCreate(
            [
                'account_id' => $accountId,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
            ],
            [
                'status' => 'active',
                'subscriber_count' => 0,
            ]
        );
    }

    /**
     * Subscribe bot or connection to stream
     */
    public function subscribe(string $streamId, string $subscriberType, int $subscriberId): ?StreamSubscription
    {
        try {
            // Check if table exists
            $subscription = new StreamSubscription();
            $tableName = $subscription->getTable();
            
            if (!Schema::hasTable($tableName)) {
                Log::error("Cannot subscribe: {$tableName} table does not exist. Please run migrations.");
                return null;
            }

            return DB::transaction(function () use ($streamId, $subscriberType, $subscriberId) {
                // Create subscription
                $subscription = StreamSubscription::firstOrCreate(
                    [
                        'stream_id' => $streamId,
                        'subscriber_type' => $subscriberType,
                        'subscriber_id' => $subscriberId,
                    ]
                );

                // Increment subscriber count
                MetaapiStream::where('id', $streamId)->increment('subscriber_count');

                Log::info('Subscribed to stream', [
                    'stream_id' => $streamId,
                    'subscriber_type' => $subscriberType,
                    'subscriber_id' => $subscriberId,
                ]);

                return $subscription;
            });
        } catch (\Exception $e) {
            Log::error('Failed to subscribe to stream', [
                'stream_id' => $streamId,
                'subscriber_type' => $subscriberType,
                'subscriber_id' => $subscriberId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Unsubscribe bot or connection from stream
     */
    public function unsubscribe(string $streamId, string $subscriberType, int $subscriberId): bool
    {
        try {
            // Check if table exists
            $subscription = new StreamSubscription();
            $tableName = $subscription->getTable();
            
            if (!Schema::hasTable($tableName)) {
                Log::warning("Cannot unsubscribe: {$tableName} table does not exist.");
                return false;
            }

            return DB::transaction(function () use ($streamId, $subscriberType, $subscriberId) {
                $deleted = StreamSubscription::where('stream_id', $streamId)
                    ->where('subscriber_type', $subscriberType)
                    ->where('subscriber_id', $subscriberId)
                    ->delete();

                if ($deleted) {
                    // Decrement subscriber count
                    MetaapiStream::where('id', $streamId)->decrement('subscriber_count');

                    Log::info('Unsubscribed from stream', [
                        'stream_id' => $streamId,
                        'subscriber_type' => $subscriberType,
                        'subscriber_id' => $subscriberId,
                    ]);
                }

                return $deleted > 0;
            });
        } catch (\Exception $e) {
            Log::error('Failed to unsubscribe from stream', [
                'stream_id' => $streamId,
                'subscriber_type' => $subscriberType,
                'subscriber_id' => $subscriberId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get active streams for account
     */
    public function getActiveStreams(string $accountId): \Illuminate\Database\Eloquent\Collection
    {
        return MetaapiStream::where('account_id', $accountId)
            ->where('status', 'active')
            ->where('subscriber_count', '>', 0)
            ->get();
    }

    /**
     * Get streams that need to be started (have subscribers but not active)
     */
    public function getStreamsToStart(string $accountId): \Illuminate\Database\Eloquent\Collection
    {
        return MetaapiStream::where('account_id', $accountId)
            ->where('status', '!=', 'active')
            ->where('subscriber_count', '>', 0)
            ->get();
    }

    /**
     * Get streams that can be stopped (no subscribers)
     */
    public function getStreamsToStop(string $accountId): \Illuminate\Database\Eloquent\Collection
    {
        return MetaapiStream::where('account_id', $accountId)
            ->where('subscriber_count', 0)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Update stream status
     */
    public function updateStreamStatus(int $streamId, string $status, ?string $error = null): bool
    {
        $update = ['status' => $status];
        if ($error) {
            $update['last_error'] = $error;
        }
        if ($status === 'active') {
            $update['last_update_at'] = now();
        }

        return MetaapiStream::where('id', $streamId)->update($update) > 0;
    }

    /**
     * Get subscriptions for subscriber
     */
    public function getSubscriptions(string $subscriberType, int $subscriberId): \Illuminate\Database\Eloquent\Collection
    {
        try {
            $subscription = new StreamSubscription();
            $tableName = $subscription->getTable();
            
            if (!Schema::hasTable($tableName)) {
                Log::warning("Table {$tableName} does not exist.");
                return new \Illuminate\Database\Eloquent\Collection([]);
            }

            return StreamSubscription::where('subscriber_type', $subscriberType)
                ->where('subscriber_id', $subscriberId)
                ->with('stream')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get subscriptions', [
                'subscriber_type' => $subscriberType,
                'subscriber_id' => $subscriberId,
                'error' => $e->getMessage(),
            ]);
            return new \Illuminate\Database\Eloquent\Collection([]);
        }
    }

    /**
     * Cleanup orphaned subscriptions
     */
    public function cleanupOrphanedSubscriptions(): int
    {
        try {
            // Check if table exists before attempting cleanup
            $subscription = new StreamSubscription();
            $tableName = $subscription->getTable();
            
            if (!Schema::hasTable($tableName)) {
                Log::warning("Table {$tableName} does not exist. Please run migrations.");
                return 0;
            }

            // Remove subscriptions where stream no longer exists
            $deleted = StreamSubscription::whereDoesntHave('stream')->delete();
            
            // Reset subscriber counts for streams with no subscriptions
            $streams = MetaapiStream::where('subscriber_count', '>', 0)
                ->whereDoesntHave('subscriptions')
                ->get();
            
            foreach ($streams as $stream) {
                $stream->update(['subscriber_count' => 0]);
            }

            return $deleted;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup orphaned subscriptions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 0;
        }
    }
}
