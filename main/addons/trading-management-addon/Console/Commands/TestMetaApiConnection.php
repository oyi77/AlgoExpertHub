<?php

namespace Addons\TradingManagement\Console\Commands;

use Illuminate\Console\Command;
use Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter;
use GuzzleHttp\Client;

class TestMetaApiConnection extends Command
{
    protected $signature = 'trading:test-metaapi 
                            {--token= : MetaApi API token}
                            {--account-id= : MetaApi account ID}';

    protected $description = 'Test MetaApi.cloud connection';

    public function handle()
    {
        $token = $this->option('token') ?: config('trading-management.metaapi.api_token') ?: $this->ask('Enter MetaApi API token');
        
        if (empty($token)) {
            $this->error('MetaApi API token is required');
            return 1;
        }

        $this->info('Exploring MetaApi API...');
        $this->newLine();

        // Test Provisioning Service
        $provisioningService = new \Addons\TradingManagement\Modules\DataProvider\Services\MetaApiProvisioningService($token);

        // Test billing info
        $this->info('Step 1: Fetching billing information from Billing API...');
        $billingInfo = $provisioningService->getBillingInfo();
        
        if ($billingInfo['success']) {
            $this->info('✓ Billing info retrieved!');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Balance', '$' . number_format($billingInfo['balance'] ?? 0, 2)],
                    ['Spending Credits', '$' . number_format($billingInfo['spending_credits'] ?? 0, 2)],
                    ['Total Available', '$' . number_format($billingInfo['total_available'] ?? 0, 2)],
                ]
            );
            if (isset($billingInfo['subscription'])) {
                $this->line('Subscription: ' . json_encode($billingInfo['subscription'], JSON_PRETTY_PRINT));
            }
            if (isset($billingInfo['usage'])) {
                $this->line('Usage: ' . json_encode($billingInfo['usage'], JSON_PRETTY_PRINT));
            }
            $this->newLine();
        } else {
            $this->warn('⚠ Failed to get billing info: ' . $billingInfo['message']);
        }

        // Test account stats
        $this->info('Step 2: Fetching account statistics from Provisioning API...');
        $accountStats = $provisioningService->getAccountStats();
        
        if ($accountStats['success']) {
            $this->info('✓ Account stats retrieved!');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Total Accounts', $accountStats['total_accounts'] ?? 0],
                    ['Active Accounts', $accountStats['active_accounts'] ?? 0],
                    ['Inactive Accounts', $accountStats['inactive_accounts'] ?? 0],
                    ['Source', $accountStats['source'] ?? 'unknown'],
                ]
            );
            $this->newLine();
        } else {
            $this->warn('⚠ Failed to get account stats: ' . ($accountStats['message'] ?? 'Unknown error'));
        }

        // Test adapter if account_id provided
        $accountId = $this->option('account-id');
        if ($accountId) {
            $this->info('Step 3: Testing adapter connection...');

        // Test with adapter
        try {
            $adapter = new MetaApiAdapter([
                'api_token' => $token,
                'account_id' => $accountId,
            ]);

            $result = $adapter->testConnection();

            if ($result['success']) {
                $this->info('✓ Connection successful!');
                $this->info('Message: ' . $result['message']);
                $this->info('Latency: ' . $result['latency'] . ' ms');
                $this->newLine();

                if (isset($result['account_info'])) {
                    $info = $result['account_info'];
                    $this->info('Account Information:');
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['Balance', number_format($info['balance'] ?? 0, 2) . ' ' . ($info['currency'] ?? 'USD')],
                            ['Equity', number_format($info['equity'] ?? 0, 2)],
                            ['Margin', number_format($info['margin'] ?? 0, 2)],
                            ['Free Margin', number_format($info['free_margin'] ?? 0, 2)],
                            ['Margin Level', number_format($info['margin_level'] ?? 0, 2) . '%'],
                            ['Leverage', '1:' . ($info['leverage'] ?? 100)],
                            ['Server', $info['server'] ?? 'N/A'],
                        ]
                    );
                }

                $this->newLine();
                $this->info('Step 3: Testing data fetching...');

                // Test fetching symbols
                try {
                    $symbols = $adapter->getAvailableSymbols();
                    $this->info('✓ Available symbols: ' . count($symbols));
                    if (count($symbols) > 0) {
                        $this->info('Sample symbols: ' . implode(', ', array_slice($symbols, 0, 10)));
                    }
                } catch (\Exception $e) {
                    $this->warn('⚠ Failed to fetch symbols: ' . $e->getMessage());
                }

                // Test fetching OHLCV
                try {
                    $testSymbol = 'EURUSD';
                    if (in_array($testSymbol, $symbols ?? [])) {
                        $this->info("Testing OHLCV fetch for {$testSymbol}...");
                        $candles = $adapter->fetchOHLCV($testSymbol, 'H1', 5);
                        $this->info('✓ Successfully fetched ' . count($candles) . ' candles');
                    } else {
                        $this->warn("⚠ {$testSymbol} not available, skipping OHLCV test");
                    }
                } catch (\Exception $e) {
                    $this->warn('⚠ Failed to fetch OHLCV: ' . $e->getMessage());
                }

                return 0;
            } else {
                $this->error('✗ Connection failed: ' . $result['message']);
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('✗ Test failed: ' . $e->getMessage());
            return 1;
        }
        } else {
            $this->warn('No account_id provided. Skipping adapter test.');
            $this->warn('To test adapter: php artisan trading:test-metaapi --token=YOUR_TOKEN --account-id=ACCOUNT_ID');
        }

        return 0;
    }
}
