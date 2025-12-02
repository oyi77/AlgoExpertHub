<?php

namespace Addons\MultiChannelSignalAddon\App\Jobs;

use Addons\MultiChannelSignalAddon\App\Adapters\TelegramMtprotoAdapter;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchTelegramDialogsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 300; // 5 minutes timeout

    protected ChannelSource $channelSource;

    public function __construct(ChannelSource $channelSource)
    {
        $this->channelSource = $channelSource;
    }

    public function handle()
    {
        try {
            $cacheKey = 'telegram_dialogs_' . $this->channelSource->id;
            $cacheKeyTimestamp = $cacheKey . '_timestamp';
            $cacheKeyFetching = $cacheKey . '_fetching';
            
            // Check if already fetching or recently fetched
            $isFetching = \Illuminate\Support\Facades\Cache::get($cacheKeyFetching);
            if ($isFetching) {
                Log::info("Dialogs fetch already in progress for channel source {$this->channelSource->id}");
                return;
            }

            // Mark as fetching
            \Illuminate\Support\Facades\Cache::put($cacheKeyFetching, true, 300); // 5 minutes

            Log::info("Starting background fetch of all dialogs for channel source {$this->channelSource->id}");

            $adapter = new TelegramMtprotoAdapter($this->channelSource);
            
            if (!$adapter->connect($this->channelSource)) {
                Log::error("Failed to connect to Telegram for channel source {$this->channelSource->id}");
                \Illuminate\Support\Facades\Cache::forget($cacheKeyFetching);
                return;
            }

            // Fetch all dialogs
            $rawDialogs = [];
            try {
                if (!\Amp\EventLoop\EventLoop::getDriver()) {
                    \Amp\EventLoop\EventLoop::run(function () use (&$rawDialogs, $adapter) {
                        $dialogsResult = $adapter->getMadeline()->getFullDialogs();
                        if ($dialogsResult instanceof \Amp\Future) {
                            $rawDialogs = \Amp\Promise\await([$dialogsResult])[0];
                        } else {
                            $rawDialogs = $dialogsResult;
                        }
                    });
                } else {
                    $dialogsResult = $adapter->getMadeline()->getFullDialogs();
                    if ($dialogsResult instanceof \Amp\Future) {
                        $rawDialogs = \Amp\Promise\await([$dialogsResult])[0];
                    } else {
                        $rawDialogs = $dialogsResult;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch dialogs in background job: " . $e->getMessage());
                \Illuminate\Support\Facades\Cache::forget($cacheKeyFetching);
                return;
            }

            $cachedRawDialogs = is_array($rawDialogs) ? array_values($rawDialogs) : [];
            
            Log::info("Background fetch completed: " . count($cachedRawDialogs) . " dialogs for channel source {$this->channelSource->id}");

            // Cache all dialogs for 1 hour
            \Illuminate\Support\Facades\Cache::put($cacheKey, $cachedRawDialogs, 3600);
            \Illuminate\Support\Facades\Cache::put($cacheKeyTimestamp, time(), 3600);
            
            // Clear fetching flag
            \Illuminate\Support\Facades\Cache::forget($cacheKeyFetching);

        } catch (\Exception $e) {
            Log::error("Error in FetchTelegramDialogsJob: " . $e->getMessage(), [
                'channel_source_id' => $this->channelSource->id,
                'exception' => $e,
            ]);
            \Illuminate\Support\Facades\Cache::forget($cacheKeyFetching ?? 'telegram_dialogs_' . $this->channelSource->id . '_fetching');
        }
    }
}

