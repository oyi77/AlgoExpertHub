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
        $token = $this->option('token') ?: $this->ask('Enter MetaApi API token');
        $accountId = $this->option('account-id') ?: $this->ask('Enter MetaApi account ID');

        if (empty($token) || empty($accountId)) {
            $this->error('Both token and account ID are required');
            return 1;
        }

        $this->info('Testing MetaApi connection...');
        $this->newLine();

        // First, try to list accounts to verify token
        $this->info('Step 1: Verifying API token...');
        try {
            $client = new Client([
                'base_uri' => 'https://mt-client-api-v1.new-york.agiliumtrade.ai',
                'timeout' => 30,
                'headers' => [
                    'Accept' => 'application/json',
                    'auth-token' => $token,
                ],
            ]);

            $response = $client->get('/users/current/accounts');
            $accounts = json_decode($response->getBody()->getContents(), true);

            if (is_array($accounts)) {
                $this->info('✓ Token is valid');
                $this->info('Found ' . count($accounts) . ' account(s) in MetaApi:');
                $this->newLine();

                $tableData = [];
                foreach ($accounts as $account) {
                    $tableData[] = [
                        'id' => $account['_id'] ?? $account['id'] ?? 'N/A',
                        'login' => $account['login'] ?? 'N/A',
                        'server' => $account['server'] ?? $account['broker'] ?? 'N/A',
                        'platform' => $account['platform'] ?? 'N/A',
                        'state' => $account['state'] ?? 'N/A',
                    ];
                }

                $this->table(['MetaApi ID', 'MT Login', 'Server', 'Platform', 'State'], $tableData);
                $this->newLine();

                // Check if provided account ID exists
                $accountExists = false;
                foreach ($accounts as $account) {
                    $metaApiId = $account['_id'] ?? $account['id'] ?? null;
                    if ($metaApiId === $accountId || (isset($account['login']) && $account['login'] == $accountId)) {
                        $accountExists = true;
                        $this->info("✓ Account ID '{$accountId}' found in your MetaApi accounts");
                        break;
                    }
                }

                if (!$accountExists) {
                    $this->warn("⚠ Account ID '{$accountId}' not found in your MetaApi accounts.");
                    $this->warn('Please use one of the MetaApi account IDs listed above.');
                    $this->newLine();
                }
            }
        } catch (\Exception $e) {
            $this->error('✗ Token verification failed: ' . $e->getMessage());
            if (strpos($e->getMessage(), '401') !== false) {
                $this->error('Invalid API token. Please check your token.');
            }
            return 1;
        }

        $this->newLine();
        $this->info('Step 2: Testing adapter connection...');

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
    }
}
