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
    protected $writeLog;

    public function __construct(string $accountId, string $apiToken)
    {
        $this->accountId = $accountId;
        $this->apiToken = $apiToken;
        $this->streamManager = app(SharedStreamManager::class);
        $this->streamingService = new MetaApiStreamingService($apiToken, $accountId);
        
        // Setup log writer
        $logFile = storage_path("logs/metaapi-stream-{$accountId}.log");
        $this->writeLog = function($message, $level = 'INFO') use ($logFile) {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] {$level}: {$message}\n";
            file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        };
    }

    /**
     * Run the worker (main loop)
     */
    public function run(): void
    {
        $writeLog = $this->writeLog;
        
        // Force immediate output
        $writeLog("MetaApiStreamWorker::run() called for account: {$this->accountId}");
        error_log("MetaApiStreamWorker::run() called for account: {$this->accountId}");
        
        // Setup signal handlers for graceful shutdown
        $this->setupSignalHandlers();
        $writeLog("Signal handlers setup complete");

        Log::info('Starting MetaAPI stream worker', [
            'account_id' => $this->accountId,
        ]);
        $writeLog("Starting MetaAPI stream worker");

        // Connect to Socket.IO (with fallback to polling)
        $writeLog("Attempting to connect MetaAPI streaming service...");
        $connected = false;
        try {
            $connected = $this->streamingService->connect();
            $isPolling = $this->streamingService->isPollingMode();
            $isSocketConnected = $this->streamingService->isConnected();
            
            // Log actual connection status
            if ($connected && $isSocketConnected && !$isPolling) {
                $writeLog("Successfully connected to MetaAPI Socket.IO (WebSocket mode)");
            } elseif ($connected && $isPolling) {
                $writeLog("Socket.IO connection failed, using REST API polling fallback mode");
            } else {
                $writeLog("Connection status: connected=" . ($connected ? 'true' : 'false') . ", polling=" . ($isPolling ? 'true' : 'false') . ", socket=" . ($isSocketConnected ? 'true' : 'false'), 'WARNING');
            }
        } catch (\Throwable $e) {
            $writeLog("Connection exception: " . $e->getMessage(), 'ERROR');
            Log::error('Exception during MetaAPI connection', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        // Verify we have a working connection (either Socket.IO or polling)
        if (!$connected) {
            $writeLog("Failed to establish any connection method", 'ERROR');
            Log::error('Failed to connect to MetaAPI Socket.IO and polling fallback also failed', [
                'account_id' => $this->accountId,
            ]);
            return;
        }
        
        // Log final mode
        if ($this->streamingService->isPollingMode()) {
            $writeLog("Running in REST API polling mode (5 second intervals)");
        } else {
            $writeLog("Running in Socket.IO real-time mode");
        }

        $writeLog("Entering main loop...");
        Log::info('MetaAPI stream worker entering main loop', [
            'account_id' => $this->accountId,
            'is_polling_mode' => $this->streamingService->isPollingMode(),
            'is_connected' => $this->streamingService->isConnected(),
        ]);

        // Main loop
        $iteration = 0;
        $lastStatusLog = 0;
        $lastPollLog = 0;
        while (!$this->shouldExit) {
            $iteration++;
            try {
                // Log status every 60 iterations (every ~6 seconds in polling mode, ~6 seconds in socket mode)
                if ($iteration % 60 === 0) {
                    $activeCount = count($this->activeStreams);
                    $mode = $this->streamingService->isPollingMode() ? 'polling' : 'socket.io';
                    $writeLog("Worker status: iteration={$iteration}, active_streams={$activeCount}, mode={$mode}");
                    Log::debug('MetaAPI stream worker iteration', [
                        'account_id' => $this->accountId,
                        'iteration' => $iteration,
                        'active_streams_count' => $activeCount,
                    ]);
                    $lastStatusLog = time();
                }

                // 1. Check for streams that need to be started
                $this->startNewStreams();

                // 2. Check for streams that can be stopped
                $this->stopUnusedStreams();

                // 3. Receive and process messages (or poll if in fallback mode)
                if ($this->streamingService->isPollingMode()) {
                    $this->pollAllStreams($writeLog);
                } else {
                    $this->processMessages();
                }

                // 4. Update stream statuses
                $this->updateStreamStatuses();

                // Small delay to prevent CPU spinning
                // In polling mode, use longer delay (5 seconds)
                $delay = $this->streamingService->isPollingMode() ? 5000000 : 100000;
                usleep($delay);
            } catch (\Throwable $e) {
                $writeLog("Exception in worker loop: " . $e->getMessage(), 'ERROR');
                $writeLog("File: " . $e->getFile() . " Line: " . $e->getLine(), 'ERROR');
                
                Log::error('Error in MetaAPI stream worker loop', [
                    'account_id' => $this->accountId,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Try to reconnect if connection lost
                if (!$this->streamingService->isConnected()) {
                    $writeLog("Connection lost, attempting reconnect...");
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
        // Get streams that need to be started (status != active but have subscribers)
        $streamsToStart = $this->streamManager->getStreamsToStart($this->accountId);

        // Also get active streams that might not be in our activeStreams array yet
        // (e.g., after worker restart, streams are marked active but not subscribed)
        $activeStreams = $this->streamManager->getActiveStreams($this->accountId);
        foreach ($activeStreams as $stream) {
            if (!isset($this->activeStreams[$stream->id])) {
                // Stream is marked active but not in our tracking - add it
                $this->activeStreams[$stream->id] = $stream;
                $writeLog = $this->writeLog;
                $writeLog("Re-added active stream to tracking: {$stream->symbol} {$stream->timeframe} (stream_id: {$stream->id})");
                Log::info('Re-added active stream to tracking', [
                    'stream_id' => $stream->id,
                    'symbol' => $stream->symbol,
                    'timeframe' => $stream->timeframe,
                ]);
            }
        }

        if ($streamsToStart->count() > 0) {
            $writeLog = $this->writeLog;
            $writeLog("Found {$streamsToStart->count()} stream(s) to start");
            Log::debug('Found streams to start', [
                'account_id' => $this->accountId,
                'count' => $streamsToStart->count(),
            ]);
        }

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
    }

    /**
     * Poll all active streams via REST API (fallback mode)
     */
    protected function pollAllStreams($writeLog = null): void
    {
        if (empty($this->activeStreams)) {
            return; // No streams to poll
        }

        $writeLog = $writeLog ?: $this->writeLog;
        static $pollCount = 0;
        $pollCount++;
        static $lastPollLogTime = 0;

        foreach ($this->activeStreams as $stream) {
            try {
                $success = $this->streamingService->pollMarketData($stream->symbol, $stream->timeframe);
                if ($success) {
                    // Log to file every 12 polls (once per minute in polling mode) or every 60 seconds
                    $now = time();
                    if ($pollCount % 12 === 0 || ($now - $lastPollLogTime) >= 60) {
                        $writeLog("Polled market data: {$stream->symbol} {$stream->timeframe} (poll #{$pollCount})");
                        $lastPollLogTime = $now;
                    }
                    Log::debug('Polled market data successfully', [
                        'stream_id' => $stream->id,
                        'symbol' => $stream->symbol,
                        'timeframe' => $stream->timeframe,
                    ]);
                } else {
                    // Log failures immediately with more context
                    $writeLog("Failed to poll market data: {$stream->symbol} {$stream->timeframe} (poll #{$pollCount})", 'WARNING');
                    Log::warning('Failed to poll market data', [
                        'stream_id' => $stream->id,
                        'symbol' => $stream->symbol,
                        'timeframe' => $stream->timeframe,
                        'poll_count' => $pollCount,
                        'hint' => 'Check MetaApiStreamingService logs for detailed error. Symbol may not exist on broker.',
                    ]);
                }
            } catch (\Exception $e) {
                $writeLog("Exception polling stream {$stream->symbol} {$stream->timeframe}: " . $e->getMessage(), 'ERROR');
                Log::error('Exception polling stream', [
                    'stream_id' => $stream->id,
                    'symbol' => $stream->symbol,
                    'timeframe' => $stream->timeframe,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
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
