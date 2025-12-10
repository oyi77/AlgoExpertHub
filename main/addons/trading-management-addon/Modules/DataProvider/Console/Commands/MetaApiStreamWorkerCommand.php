<?php

namespace Addons\TradingManagement\Modules\DataProvider\Console\Commands;

use Addons\TradingManagement\Modules\DataProvider\Workers\MetaApiStreamWorker;
use Addons\TradingManagement\Modules\DataProvider\Services\MetaApiProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * MetaApiStreamWorker Command
 * 
 * Long-running daemon for streaming MetaAPI market data
 */
class MetaApiStreamWorkerCommand extends Command
{
    protected $signature = 'metaapi:stream-worker {account_id}';
    protected $description = 'Run MetaAPI stream worker daemon for account';

    public function handle()
    {
        $accountId = $this->argument('account_id');
        
        // Get API token from config or global settings
        $apiToken = config('trading-management.metaapi.api_token');
        if (!$apiToken) {
            // Try to get from global settings
            try {
                $globalConfig = \App\Services\GlobalConfigurationService::get('metaapi_global_settings', []);
                if (!empty($globalConfig['api_token'])) {
                    try {
                        $apiToken = \Illuminate\Support\Facades\Crypt::decryptString($globalConfig['api_token']);
                    } catch (\Exception $e) {
                        $apiToken = $globalConfig['api_token'];
                    }
                }
            } catch (\Exception $e) {
                // Ignore
            }
        }

        if (!$apiToken) {
            $this->error('MetaAPI API token is required. Please configure METAAPI_TOKEN in .env');
            return 1;
        }

        $this->info("Starting MetaAPI stream worker for account: {$accountId}");
        
        // Get log file path for direct writing
        $logFile = storage_path("logs/metaapi-stream-{$accountId}.log");
        
        // Helper function to write to both log file and Laravel log
        $writeLog = function($message, $level = 'INFO') use ($logFile, $accountId) {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] {$level}: {$message}\n";
            file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
            error_log($message);
        };
        
        // Force log output immediately
        $writeLog("MetaAPI stream worker command started for account: {$accountId}");
        Log::info('MetaAPI stream worker command started', ['account_id' => $accountId]);
        
        // Flush output
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();

        try {
            $writeLog("Creating MetaApiStreamWorker instance...");
            $worker = new MetaApiStreamWorker($accountId, $apiToken);
            $writeLog("MetaAPI stream worker instance created, calling run()");
            Log::info('MetaAPI stream worker instance created', ['account_id' => $accountId]);
            
            $writeLog("Entering worker run() method...");
            $worker->run();
            
            $writeLog("MetaAPI stream worker run() completed");
            Log::info('MetaAPI stream worker run() completed', ['account_id' => $accountId]);
        } catch (\Throwable $e) {
            $errorMsg = "MetaAPI stream worker failed: " . $e->getMessage();
            $writeLog($errorMsg, 'ERROR');
            $writeLog("File: " . $e->getFile() . " Line: " . $e->getLine(), 'ERROR');
            $writeLog("Stack trace: " . $e->getTraceAsString(), 'ERROR');
            
            Log::error('MetaAPI stream worker failed', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
