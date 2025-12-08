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

        try {
            $worker = new MetaApiStreamWorker($accountId, $apiToken);
            $worker->run();
        } catch (\Exception $e) {
            Log::error('MetaAPI stream worker failed', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
