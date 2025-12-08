<?php

namespace Addons\TradingManagement\Modules\DataProvider\Workers;

use Addons\TradingManagement\Modules\DataProvider\Services\MetaApiStreamingService;
use Addons\TradingManagement\Modules\DataProvider\Services\SharedStreamManager;
use Addons\TradingManagement\Modules\DataProvider\Models\MetaapiStream;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * MetaApiStreamWorker
 * 
 * Long-running daemon process for streaming market data from MetaAPI
 * Maintains WebSocket connections and streams data for all active symbols/timeframes
 */
class MetaApiStreamWorker
{
    protected MetaApiStreamingService $streamingService;
    protected SharedStreamManager $streamManager;
    protected string $accountId;
    protected string $apiToken;
    protected bool $shouldExit = false;
    protected array $activeStreams = [];

    public function __construct(string $accountId, string $apiToken)
    {
        $this->accountId = $accountId;
        $this->apiToken = $apiToken;
        $this->streamManager = app(SharedStreamManager::class);
        $this->streamingService = new MetaApiStreamingService($apiToken, $accountId);
    }

    /**
     * Run the worker (main loop)
     */
    public function run(): void
    {
        // Setup signal handlers for graceful shutdown
        $this->setupSignalHandlers();

        Log::info('Starting MetaAPI stream worker', [
            'account_id' => $this->accountId,
        ]);

        // Connect to Socket.IO
        if (!$this->streamingService->connect()) {
            Log::error('Failed to connect to MetaAPI Socket.IO', [
                'account_id' => $this->accountId,
            ]);
            return;
        }

        // Main loop
        while (!$this->shouldExit) {
            try {
                // 1. Check for streams that need to be started
                $this->startNewStreams();

                // 2. Check for streams that can be stopped
                $this->stopUnusedStreams();

                // 3. Receive and process messages
                $this->processMessages();

                // 4. Update stream statuses
                $this->updateStreamStatuses();

                // Small delay to prevent CPU spinning
                usleep(100000); // 0.1 seconds
            } catch (\Exception $e) {
                Log::error('Error in MetaAPI stream worker loop', [
                    'account_id' => $this->accountId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Try to reconnect if connection lost
                if (!$this->streamingService->isConnected()) {
                    $this->reconnect();
                }

                sleep(5); // Wait before retrying
            }
        }

        // Cleanup
        $this->cleanup();
    }

    /**
     * Start new streams that have subscribers
     */
    protected function startNewStreams(): void
    {
        $streamsToStart = $this->streamManager->getStreamsToStart($this->accountId);

        foreach ($streamsToStart as $stream) {
            try {
                if ($this->streamingService->subscribe($stream->symbol, $stream->timeframe)) {
                    $this->streamManager->updateStreamStatus($stream->id, 'active');
                    $this->activeStreams[$stream->id] = $stream;
                    
                    Log::info('Started MetaAPI stream', [
                        'stream_id' => $stream->id,
                        'symbol' => $stream->symbol,
                        'timeframe' => $stream->timeframe,
                    ]);
                } else {
                    $this->streamManager->updateStreamStatus($stream->id, 'error', 'Failed to subscribe');
                }
            } catch (\Exception $e) {
                Log::error('Failed to start stream', [
                    'stream_id' => $stream->id,
                    'error' => $e->getMessage(),
                ]);
                $this->streamManager->updateStreamStatus($stream->id, 'error', $e->getMessage());
            }
        }
    }

    /**
     * Stop streams that have no subscribers
     */
    protected function stopUnusedStreams(): void
    {
        $streamsToStop = $this->streamManager->getStreamsToStop($this->accountId);

        foreach ($streamsToStop as $stream) {
            try {
                if ($this->streamingService->unsubscribe($stream->symbol, $stream->timeframe)) {
                    $this->streamManager->updateStreamStatus($stream->id, 'paused');
                    unset($this->activeStreams[$stream->id]);
                    
                    Log::info('Stopped MetaAPI stream', [
                        'stream_id' => $stream->id,
                        'symbol' => $stream->symbol,
                        'timeframe' => $stream->timeframe,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to stop stream', [
                    'stream_id' => $stream->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Process incoming Socket.IO messages
     * 
     * Note: With Socket.IO, messages are handled via event listeners in MetaApiStreamingService
     * This method keeps the connection alive and allows event listeners to process messages
     */
    protected function processMessages(): void
    {
        // With Socket.IO, messages are handled via event listeners set up in MetaApiStreamingService
        // We just need to keep the connection alive and let the listeners process messages
        
        // Try to wait for messages (if the library supports it)
        $this->streamingService->waitForMessages(1);
        
        // Small delay to prevent CPU spinning
        usleep(100000); // 0.1 seconds
    }

    /**
     * Update stream statuses (mark as active if receiving data)
     */
    protected function updateStreamStatuses(): void
    {
        foreach ($this->activeStreams as $stream) {
            // Check if stream is still receiving data (check Redis)
            $cacheKey = sprintf(
                '%s:%s:%s:%s',
                config('trading-management.metaapi.streaming.redis_prefix', 'metaapi:stream'),
                $this->accountId,
                $stream->symbol,
                $stream->timeframe
            );

            if (Cache::has($cacheKey)) {
                $this->streamManager->updateStreamStatus($stream->id, 'active');
            }
        }
    }

    /**
     * Reconnect to Socket.IO
     */
    protected function reconnect(): void
    {
        Log::info('Attempting to reconnect to MetaAPI Socket.IO', [
            'account_id' => $this->accountId,
        ]);

        $this->streamingService->disconnect();
        sleep(config('trading-management.metaapi.streaming.reconnect_delay', 5));

        if ($this->streamingService->connect()) {
            // Resubscribe to all active streams
            foreach ($this->activeStreams as $stream) {
                $this->streamingService->subscribe($stream->symbol, $stream->timeframe);
            }
        }
    }

    /**
     * Setup signal handlers for graceful shutdown
     */
    protected function setupSignalHandlers(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
        }
    }

    /**
     * Handle shutdown signals
     */
    public function handleSignal(int $signal): void
    {
        Log::info('Received shutdown signal', ['signal' => $signal]);
        $this->shouldExit = true;
    }

    /**
     * Cleanup on exit
     */
    protected function cleanup(): void
    {
        Log::info('Cleaning up MetaAPI stream worker', [
            'account_id' => $this->accountId,
        ]);

        // Unsubscribe from all streams
        foreach ($this->activeStreams as $stream) {
            try {
                $this->streamingService->unsubscribe($stream->symbol, $stream->timeframe);
            } catch (\Exception $e) {
                Log::warning('Error unsubscribing during cleanup', [
                    'stream_id' => $stream->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Disconnect
        $this->streamingService->disconnect();
    }
}
