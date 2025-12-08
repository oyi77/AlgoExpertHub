<?php

namespace Addons\TradingManagement\Modules\DataProvider\Jobs;

use Addons\TradingManagement\Modules\DataProvider\Services\SharedStreamManager;
use Addons\TradingManagement\Modules\DataProvider\Models\MetaapiStream;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * MonitorStreamHealthJob
 * 
 * Monitors stream health, restarts failed streams, cleanup orphaned subscriptions
 */
class MonitorStreamHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 120;

    public function handle()
    {
        try {
            $streamManager = app(SharedStreamManager::class);

            // 1. Cleanup orphaned subscriptions (gracefully handle missing table)
            try {
                $cleaned = $streamManager->cleanupOrphanedSubscriptions();
                if ($cleaned > 0) {
                    Log::info('Cleaned up orphaned stream subscriptions', ['count' => $cleaned]);
                }
            } catch (\Exception $e) {
                Log::warning('Skipped cleanup of orphaned subscriptions', [
                    'error' => $e->getMessage(),
                    'note' => 'This is normal if migrations have not been run yet'
                ]);
            }

            // 2. Check for streams with errors that have subscribers (should be restarted)
            $errorStreams = MetaapiStream::where('status', 'error')
                ->where('subscriber_count', '>', 0)
                ->get();

            foreach ($errorStreams as $stream) {
                Log::warning('Stream in error state but has subscribers', [
                    'stream_id' => $stream->id,
                    'account_id' => $stream->account_id,
                    'symbol' => $stream->symbol,
                    'timeframe' => $stream->timeframe,
                    'subscriber_count' => $stream->subscriber_count,
                ]);
                
                // Mark as active to trigger restart
                $streamManager->updateStreamStatus($stream->id, 'active');
            }

            // 3. Check for streams that haven't updated in a while
            $staleThreshold = now()->subMinutes(5);
            $staleStreams = MetaapiStream::where('status', 'active')
                ->where('subscriber_count', '>', 0)
                ->where(function ($query) use ($staleThreshold) {
                    $query->whereNull('last_update_at')
                        ->orWhere('last_update_at', '<', $staleThreshold);
                })
                ->get();

            foreach ($staleStreams as $stream) {
                Log::warning('Stream appears stale (no updates)', [
                    'stream_id' => $stream->id,
                    'account_id' => $stream->account_id,
                    'symbol' => $stream->symbol,
                    'timeframe' => $stream->timeframe,
                    'last_update_at' => $stream->last_update_at,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Stream health monitoring job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
