<?php

namespace App\Listeners;

use Laravel\Octane\Events\WorkerStarting;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TaskTerminated;
use Illuminate\Support\Facades\Log;

/**
 * Suppress Swoole warnings that are harmless but noisy
 * 
 * - SIGPIPE (Broken pipe) warnings occur when clients disconnect early (normal behavior)
 * - Server::finish() warnings occur when called outside worker context (handled gracefully)
 * 
 * Note: Swoole's internal warnings (swoole_http.log) are written directly by the C extension
 * and cannot be suppressed via PHP. These warnings are harmless and can be safely ignored.
 * To reduce log file size, consider:
 * 1. Setting up log rotation for swoole_http.log
 * 2. Filtering the log file with a cron job
 * 3. Configuring Swoole's log level (if supported by your Swoole version)
 */
class SuppressSwooleWarnings
{
    /**
     * Handle worker starting event
     * Set up signal handlers to suppress SIGPIPE warnings
     */
    public function handleWorkerStarting(WorkerStarting $event): void
    {
        if (!extension_loaded('swoole')) {
            return;
        }

        // Suppress SIGPIPE warnings by ignoring the signal
        // This prevents "Unable to find callback function for signal Broken pipe: 13" warnings
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGPIPE, SIG_IGN);
            // Process any pending signals immediately
            pcntl_signal_dispatch();
        }

        // Set up error handler to catch and suppress specific Swoole warnings
        // Note: This only affects warnings that go through PHP's error handler
        // Swoole's internal C logging (swoole_http.log) bypasses this
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            // Suppress specific Swoole warnings
            if (strpos($errstr, 'swoole_signal_callback') !== false && 
                strpos($errstr, 'Broken pipe') !== false) {
                return true; // Suppress this warning
            }
            if (strpos($errstr, 'Server::finish()') !== false) {
                return true; // Suppress this warning
            }
            // Let other errors through
            return false;
        }, E_WARNING);
    }

    /**
     * Handle task received - ensure we're in correct context
     */
    public function handleTaskReceived(TaskReceived $event): void
    {
        // Ensure signal handlers are set for task workers too
        if (extension_loaded('swoole') && function_exists('pcntl_signal')) {
            pcntl_signal(SIGPIPE, SIG_IGN);
            pcntl_signal_dispatch();
        }
    }

    /**
     * Handle task terminated - cleanup
     */
    public function handleTaskTerminated(TaskTerminated $event): void
    {
        // Task completed, no action needed
    }
}

