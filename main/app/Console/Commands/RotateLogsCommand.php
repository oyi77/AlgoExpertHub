<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LogRotationService;
use Illuminate\Support\Facades\File;

/**
 * RotateLogsCommand
 * 
 * Rotates log files to keep them small
 */
class RotateLogsCommand extends Command
{
    protected $signature = 'logs:rotate {--max-lines=1000 : Maximum lines to keep}';
    protected $description = 'Rotate log files to keep only the last N lines';

    public function handle()
    {
        $maxLines = (int) $this->option('max-lines');
        $rotationService = app(LogRotationService::class);
        $rotationService->setMaxLines($maxLines);

        $logsPath = storage_path('logs');
        $rotatedCount = 0;

        // Rotate trading-bot logs
        $tradingBotLogs = File::glob($logsPath . '/trading-bot-*.log');
        foreach ($tradingBotLogs as $logFile) {
            if ($rotationService->rotateIfNeeded($logFile, $maxLines)) {
                $rotatedCount++;
                $this->info("Rotated: " . basename($logFile));
            }
        }

        // Rotate metaapi-stream logs
        $metaapiLogs = File::glob($logsPath . '/metaapi-stream-*.log');
        foreach ($metaapiLogs as $logFile) {
            if ($rotationService->rotateIfNeeded($logFile, $maxLines)) {
                $rotatedCount++;
                $this->info("Rotated: " . basename($logFile));
            }
        }

        if ($rotatedCount > 0) {
            $this->info("Rotated {$rotatedCount} log file(s)");
        } else {
            $this->info("No log files needed rotation");
        }

        return 0;
    }
}
