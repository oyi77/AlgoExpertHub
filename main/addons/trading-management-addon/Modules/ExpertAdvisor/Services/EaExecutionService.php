<?php

namespace Addons\TradingManagement\Modules\ExpertAdvisor\Services;

use Addons\TradingManagement\Modules\ExpertAdvisor\Models\ExpertAdvisor;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * EaExecutionService
 * 
 * Handles Expert Advisor execution for trading bots
 * Integrates MT4/MT5 EA files with bot execution flow
 */
class EaExecutionService
{
    /**
     * Execute EA and get trading signals
     * 
     * @param ExpertAdvisor $ea
     * @param TradingBot $bot
     * @param array $marketData Current market data (OHLCV)
     * @return array ['signal' => 'buy|sell|hold', 'entry_price' => float, 'sl' => float, 'tp' => float, 'confidence' => float]
     */
    public function executeEa(ExpertAdvisor $ea, TradingBot $bot, array $marketData): array
    {
        try {
            // Load EA parameters
            $parameters = $this->loadEaParameters($ea, $bot);

            // Get connection for EA execution
            $connection = $bot->exchangeConnection;
            if (!$connection) {
                return $this->defaultHold();
            }

            // Execute EA based on type
            if ($ea->ea_type === 'mt4') {
                return $this->executeMt4Ea($ea, $connection, $marketData, $parameters);
            } elseif ($ea->ea_type === 'mt5') {
                return $this->executeMt5Ea($ea, $connection, $marketData, $parameters);
            }

            return $this->defaultHold();
        } catch (\Exception $e) {
            Log::error('EA execution failed', [
                'ea_id' => $ea->id,
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);
            return $this->defaultHold();
        }
    }

    /**
     * Load EA parameters (merge default with bot-specific)
     */
    protected function loadEaParameters(ExpertAdvisor $ea, TradingBot $bot): array
    {
        $defaultParams = $ea->default_parameters ?? [];
        $eaParams = $ea->parameters ?? [];

        // Bot-specific EA parameters could be stored in bot settings
        // For now, use EA's parameters
        return array_merge($defaultParams, $eaParams);
    }

    /**
     * Execute MT4 EA
     */
    protected function executeMt4Ea(ExpertAdvisor $ea, ExchangeConnection $connection, array $marketData, array $parameters): array
    {
        // MT4 EA execution via MetaAPI or MTAPI
        if ($connection->provider === 'metaapi') {
            return $this->executeViaMetaApi($ea, $connection, $marketData, $parameters, 'mt4');
        } else {
            // Use MTAPI for MT4
            return $this->executeViaMtapi($ea, $connection, $marketData, $parameters, 'mt4');
        }
    }

    /**
     * Execute MT5 EA
     */
    protected function executeMt5Ea(ExpertAdvisor $ea, ExchangeConnection $connection, array $marketData, array $parameters): array
    {
        // MT5 EA execution via MetaAPI or MTAPI
        if ($connection->provider === 'metaapi') {
            return $this->executeViaMetaApi($ea, $connection, $marketData, $parameters, 'mt5');
        } else {
            // Use MTAPI for MT5
            return $this->executeViaMtapi($ea, $connection, $marketData, $parameters, 'mt5');
        }
    }

    /**
     * Execute EA via MetaAPI
     * 
     * MetaAPI allows running EA via RPC request
     * The EA must be uploaded to the MT4/MT5 terminal first
     */
    protected function executeViaMetaApi(ExpertAdvisor $ea, ExchangeConnection $connection, array $marketData, array $parameters, string $platform): array
    {
        try {
            $accountId = $connection->credentials['account_id'] ?? null;
            $apiToken = $connection->credentials['api_token'] ?? config('trading-management.metaapi.api_token');

            if (!$accountId || !$apiToken) {
                Log::warning('MetaAPI credentials missing for EA execution', [
                    'ea_id' => $ea->id,
                    'connection_id' => $connection->id,
                ]);
                return $this->defaultHold();
            }

            // Get base URL
            $baseUrl = config('trading-management.metaapi.base_url', 'https://mt-client-api-v1.london.agiliumtrade.ai');

            // MetaAPI RPC endpoint for running EA
            $endpoint = "{$baseUrl}/users/current/accounts/{$accountId}/rpc";

            // Prepare RPC request to execute EA
            // MetaAPI RPC format: { type: "executeEA", eaName: "...", parameters: {...} }
            $rpcRequest = [
                'type' => 'executeEA',
                'eaName' => $ea->name,
                'eaFile' => $ea->ea_file_path,
                'parameters' => $parameters,
                'symbol' => $marketData[0]['symbol'] ?? null,
                'timeframe' => $marketData[0]['timeframe'] ?? null,
            ];

            // Make RPC request
            $response = Http::withHeaders([
                'auth-token' => $apiToken,
                'Content-Type' => 'application/json',
            ])->post($endpoint, [
                'application' => 'MetaApi',
                'requestId' => uniqid('ea_', true),
                'request' => $rpcRequest,
            ]);

            if (!$response->successful()) {
                Log::error('MetaAPI EA RPC request failed', [
                    'ea_id' => $ea->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return $this->defaultHold();
            }

            $data = $response->json();
            $eaResult = $data['response'] ?? $data;

            // Parse EA result
            // Expected format: { signal: 'buy|sell|hold', entryPrice: float, sl: float, tp: float, confidence: float }
            if (isset($eaResult['signal']) && $eaResult['signal'] !== 'hold') {
                return [
                    'signal' => strtolower($eaResult['signal']),
                    'entry_price' => (float) ($eaResult['entryPrice'] ?? $eaResult['entry_price'] ?? $marketData[0]['close'] ?? 0),
                    'sl' => (float) ($eaResult['sl'] ?? $eaResult['stopLoss'] ?? null),
                    'tp' => (float) ($eaResult['tp'] ?? $eaResult['takeProfit'] ?? null),
                    'confidence' => (float) ($eaResult['confidence'] ?? 0.8),
                ];
            }

            return $this->defaultHold();
        } catch (\Exception $e) {
            Log::error('MetaAPI EA execution failed', [
                'ea_id' => $ea->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->defaultHold();
        }
    }

    /**
     * Execute EA via MTAPI
     * 
     * MTAPI provides endpoints to run EA and get signals
     */
    protected function executeViaMtapi(ExpertAdvisor $ea, ExchangeConnection $connection, array $marketData, array $parameters, string $platform): array
    {
        try {
            $mtapiKey = $connection->credentials['api_key'] ?? config('trading-management.mtapi.api_key');
            $mtapiUrl = config('trading-management.mtapi.base_url', 'https://api.mtapi.io');

            if (!$mtapiKey) {
                Log::warning('MTAPI key missing for EA execution', [
                    'ea_id' => $ea->id,
                    'connection_id' => $connection->id,
                ]);
                return $this->defaultHold();
            }

            // MTAPI EA execution endpoint
            $endpoint = "{$mtapiUrl}/v1/ea/execute";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$mtapiKey}",
                'Content-Type' => 'application/json',
            ])->post($endpoint, [
                'ea_file' => $ea->ea_file_path,
                'ea_name' => $ea->name,
                'platform' => $platform,
                'parameters' => $parameters,
                'market_data' => $marketData,
            ]);

            if (!$response->successful()) {
                Log::error('MTAPI EA execution request failed', [
                    'ea_id' => $ea->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return $this->defaultHold();
            }

            $data = $response->json();
            $eaResult = $data['result'] ?? $data;

            // Parse EA result
            if (isset($eaResult['signal']) && $eaResult['signal'] !== 'hold') {
                return [
                    'signal' => strtolower($eaResult['signal']),
                    'entry_price' => (float) ($eaResult['entry_price'] ?? $marketData[0]['close'] ?? 0),
                    'sl' => (float) ($eaResult['sl'] ?? null),
                    'tp' => (float) ($eaResult['tp'] ?? null),
                    'confidence' => (float) ($eaResult['confidence'] ?? 0.8),
                ];
            }

            return $this->defaultHold();
        } catch (\Exception $e) {
            Log::error('MTAPI EA execution failed', [
                'ea_id' => $ea->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->defaultHold();
        }
    }

    /**
     * Default hold signal (when EA doesn't provide signal)
     */
    protected function defaultHold(): array
    {
        return [
            'signal' => 'hold',
            'entry_price' => null,
            'sl' => null,
            'tp' => null,
            'confidence' => 0,
        ];
    }
}
