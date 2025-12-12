<?php

namespace Addons\TradingManagement\Modules\DataProvider\Adapters;

use Addons\TradingManagement\Shared\Contracts\DataProviderInterface;
use Addons\TradingManagement\Shared\Contracts\ExchangeAdapterInterface;
use ccxt\Exchange;
use Exception;

/**
 * CCXT Adapter
 * 
 * Implements DataProviderInterface and ExchangeAdapterInterface via CCXT
 */
class CcxtAdapter implements DataProviderInterface, ExchangeAdapterInterface
{
    protected ?Exchange $exchange = null;
    protected array $credentials = [];
    protected string $exchangeId;
    protected bool $connected = false;

    public function __construct(string $exchangeId, array $credentials = [])
    {
        $this->exchangeId = $exchangeId;
        $this->credentials = $credentials;
        
        $this->initializeExchange();
    }

    protected function initializeExchange(): void
    {
        $exchangeClass = "\\ccxt\\$this->exchangeId";
        if (!class_exists($exchangeClass)) {
            throw new Exception("Exchange $this->exchangeId not supported by CCXT");
        }

        $this->exchange = new $exchangeClass([
            'apiKey' => $this->credentials['api_key'] ?? null,
            'secret' => $this->credentials['secret'] ?? null,
            'password' => $this->credentials['password'] ?? null, // For some exchanges like OKX/KuCoin
            'uid' => $this->credentials['uid'] ?? null,
            'enableRateLimit' => true,
        ]);
        
        // Use sandbox if configured
        if (!empty($this->credentials['sandbox'])) {
            $this->exchange->set_sandbox_mode(true);
        }
    }

    public function connect(array $credentials = []): bool
    {
        if (!empty($credentials)) {
            $this->credentials = array_merge($this->credentials, $credentials);
            $this->initializeExchange();
        }

        try {
            // Some exchanges don't require explicit check, but loading markets confirms connectivity
            $this->exchange->load_markets();
            $this->connected = true;
            return true;
        } catch (Exception $e) {
            $this->connected = false;
            throw new Exception("Failed to connect to $this->exchangeId: " . $e->getMessage());
        }
    }

    public function disconnect(): void
    {
        $this->connected = false;
        $this->exchange = null;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function fetchOHLCV(string $symbol, string $timeframe, int $limit = 100, ?int $since = null): array
    {
        if (!$this->connected) {
            $this->connect();
        }

        try {
            $ohlcv = $this->exchange->fetch_ohlcv($symbol, $timeframe, $since, $limit);
            return $this->normalizeOHLCVData($ohlcv);
        } catch (Exception $e) {
            throw new Exception("Failed to fetch OHLCV from $this->exchangeId: " . $e->getMessage());
        }
    }

    public function fetchTicks(string $symbol, int $limit = 100): array
    {
        if (!$this->connected) {
            $this->connect();
        }

        try {
            // CCXT doesn't have a standard fetch_ticks for all exchanges, 
            // but fetch_trades (public trades) is often what's intended for tick data history
            $trades = $this->exchange->fetch_trades($symbol, null, $limit);
            
            $ticks = [];
            foreach ($trades as $trade) {
                $ticks[] = [
                    'timestamp' => $trade['timestamp'],
                    'price' => $trade['price'],
                    'volume' => $trade['amount'],
                    'side' => $trade['side'],
                ];
            }
            return $ticks;
        } catch (Exception $e) {
            throw new Exception("Failed to fetch ticks from $this->exchangeId: " . $e->getMessage());
        }
    }

    public function getAccountInfo(): array
    {
        if (!$this->connected) {
            $this->connect();
        }

        try {
            $balance = $this->exchange->fetch_balance();
            return [
                'free' => $balance['free'],
                'used' => $balance['used'],
                'total' => $balance['total'],
                'info' => $balance['info'] ?? [],
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to get account info from $this->exchangeId: " . $e->getMessage());
        }
    }

    public function getAvailableSymbols(): array
    {
        if (!$this->connected) {
            $this->connect();
        }

        return $this->exchange->symbols;
    }

    public function testConnection(): array
    {
        $start = microtime(true);
        try {
            $this->connect();
            $latency = (microtime(true) - $start) * 1000;
            return [
                'success' => true,
                'message' => "Successfully connected to $this->exchangeId",
                'latency' => round($latency, 2),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'latency' => round((microtime(true) - $start) * 1000, 2),
            ];
        }
    }

    public function getProviderName(): string
    {
        return "ccxt_$this->exchangeId";
    }

    protected function normalizeOHLCVData(array $data): array
    {
        // CCXT returns [timestamp, open, high, low, close, volume]
        $normalized = [];
        foreach ($data as $candle) {
            $normalized[] = [
                'timestamp' => $candle[0],
                'open' => $candle[1],
                'high' => $candle[2],
                'low' => $candle[3],
                'close' => $candle[4],
                'volume' => $candle[5],
            ];
        }
        return $normalized;
    }

    // ExchangeAdapterInterface Implementation

    public function createMarketOrder(string $symbol, string $side, float $amount, array $params = []): array
    {
        if (!$this->connected) $this->connect();
        try {
            return $this->exchange->create_market_order($symbol, $side, $amount, null, $params);
        } catch (Exception $e) {
            throw new Exception("Create market order failed: " . $e->getMessage());
        }
    }

    public function createLimitOrder(string $symbol, string $side, float $amount, float $price, array $params = []): array
    {
        if (!$this->connected) $this->connect();
        try {
            return $this->exchange->create_limit_order($symbol, $side, $amount, $price, $params);
        } catch (Exception $e) {
            throw new Exception("Create limit order failed: " . $e->getMessage());
        }
    }

    public function cancelOrder(string $orderId, string $symbol): array
    {
        if (!$this->connected) $this->connect();
        try {
            return $this->exchange->cancel_order($orderId, $symbol);
        } catch (Exception $e) {
            throw new Exception("Cancel order failed: " . $e->getMessage());
        }
    }

    public function getOrder(string $orderId, string $symbol): array
    {
        if (!$this->connected) $this->connect();
        try {
            return $this->exchange->fetch_order($orderId, $symbol);
        } catch (Exception $e) {
            throw new Exception("Get order failed: " . $e->getMessage());
        }
    }

    public function getOpenPositions(?string $symbol = null): array
    {
        if (!$this->connected) $this->connect();
        try {
            // Not all exchanges support fetchPositions
            if ($this->exchange->has['fetchPositions']) {
                return $this->exchange->fetch_positions($symbol ? [$symbol] : null);
            }
            return [];
        } catch (Exception $e) {
            // Fallback: return empty or throw? Usually better to log and return empty if not critical
            return [];
        }
    }

    public function closePosition(string $positionId, string $symbol, ?float $amount = null): array
    {
        if (!$this->connected) $this->connect();
        
        try {
            // 1. Try native close position if supported (e.g. OKX, Binance Futures sometimes support it)
            // CCXT doesn't have a unified 'closePosition' method yet, usually it's exchange specific.
            
            // 2. Fetch position to determine side/amount if not provided
            $positions = $this->getOpenPositions($symbol);
            $targetPosition = null;
            
            // Try to find by multiple criteria since IDs vary
            foreach ($positions as $p) {
                if (($p['id'] ?? '') == $positionId || ($p['symbol'] ?? '') == $symbol) {
                    $targetPosition = $p;
                    break;
                }
            }
            
            if (!$targetPosition) {
                // If we can't find it but have symbol, maybe just skip or error
                throw new Exception("Position not found for $symbol");
            }
            
            $side = $targetPosition['side'] ?? null; // 'long' or 'short' usually
            $size = $amount ?? $targetPosition['contracts'] ?? $targetPosition['amount'] ?? 0;
            
            if (!$side || $size <= 0) {
                 throw new Exception("Could not determine position side or size");
            }
            
            // Determine opposite side
            $closeSide = ($side === 'long' || $side === 'buy') ? 'sell' : 'buy';
            
            // 3. Place opposite market order
            // Note: Some exchanges require 'reduceOnly' => true
            $params = ['reduceOnly' => true];
            
            return $this->createMarketOrder($symbol, $closeSide, $size, $params);
            
        } catch (Exception $e) {
             throw new Exception("Close position failed: " . $e->getMessage());
        }
    }

    public function getBalance(): array
    {
        return $this->getAccountInfo();
    }

    public function modifyPosition(string $positionId, string $symbol, ?float $stopLoss = null, ?float $takeProfit = null): array
    {
         // CCXT doesn't have generic modifyPosition.
         // Usually involves cancelling SL/TP orders and creating new ones.
         throw new Exception("modifyPosition not supported in generic CCXT adapter yet.");
    }

    public function getCurrentPrice(string $symbol): array
    {
        if (!$this->connected) $this->connect();
        try {
            $ticker = $this->exchange->fetch_ticker($symbol);
            return [
                'bid' => $ticker['bid'],
                'ask' => $ticker['ask'],
                'last' => $ticker['last'],
                'timestamp' => $ticker['timestamp'],
            ];
        } catch (Exception $e) {
            throw new Exception("Get current price failed: " . $e->getMessage());
        }
    }

    public function getExchangeName(): string
    {
        return $this->exchangeId;
    }
}
