<?php

namespace Addons\TradingExecutionEngine\App\Adapters;

use Illuminate\Support\Facades\Log;

class CryptoExchangeAdapter extends BaseExchangeAdapter
{
    protected $exchange = null;

    /**
     * Initialize ccxt exchange instance.
     */
    protected function initializeExchange()
    {
        if ($this->exchange !== null) {
            return;
        }

        try {
            // Check if ccxt is available
            if (!class_exists('\ccxt\Exchange')) {
                throw new \Exception('ccxt library is not installed. Please install it via: composer require ccxt/ccxt');
            }

            $exchangeName = $this->connection->exchange_name;
            $credentials = $this->credentials;

            // Create exchange instance
            $exchangeClass = "\\ccxt\\{$exchangeName}";
            
            if (!class_exists($exchangeClass)) {
                throw new \Exception("Exchange {$exchangeName} is not supported by ccxt");
            }

            $this->exchange = new $exchangeClass([
                'apiKey' => $credentials['api_key'] ?? '',
                'secret' => $credentials['api_secret'] ?? '',
                'password' => $credentials['api_passphrase'] ?? null, // For some exchanges like Coinbase Pro
                'enableRateLimit' => true,
                'options' => [
                    'defaultType' => $credentials['default_type'] ?? 'spot', // spot, margin, future
                ],
            ]);

            $this->connected = true;
        } catch (\Exception $e) {
            $this->logError("Failed to initialize exchange: " . $e->getMessage());
            throw $e;
        }
    }

    public function getExchangeName(): string
    {
        return $this->connection->exchange_name;
    }

    public function validateCredentials(array $credentials): bool
    {
        return isset($credentials['api_key']) && isset($credentials['api_secret']);
    }

    public function testConnection(array $credentials): array
    {
        try {
            $this->credentials = $credentials;
            $this->initializeExchange();

            // Try to fetch balance as connection test
            $balance = $this->exchange->fetchBalance();
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'data' => [
                    'balance' => $balance['total'] ?? [],
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    public function placeMarketOrder(string $symbol, string $direction, float $quantity, array $options = []): array
    {
        try {
            $this->initializeExchange();

            $side = strtolower($direction) === 'buy' ? 'buy' : 'sell';
            $type = 'market';

            $order = $this->exchange->createMarketOrder($symbol, $side, $quantity, null, null, $options);

            // Set SL/TP if provided
            if (isset($options['sl_price'])) {
                // Some exchanges support stop-loss orders, implement if needed
            }

            if (isset($options['tp_price'])) {
                // Some exchanges support take-profit orders, implement if needed
            }

            return [
                'success' => true,
                'order_id' => $order['id'] ?? null,
                'price' => $order['price'] ?? $order['average'] ?? null,
                'message' => 'Order placed successfully',
                'data' => $order,
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to place market order: " . $e->getMessage(), [
                'symbol' => $symbol,
                'direction' => $direction,
                'quantity' => $quantity,
            ]);

            return [
                'success' => false,
                'order_id' => null,
                'price' => null,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    public function placeLimitOrder(string $symbol, string $direction, float $quantity, float $price, array $options = []): array
    {
        try {
            $this->initializeExchange();

            $side = strtolower($direction) === 'buy' ? 'buy' : 'sell';
            $type = 'limit';

            $order = $this->exchange->createLimitOrder($symbol, $side, $quantity, $price, null, $options);

            return [
                'success' => true,
                'order_id' => $order['id'] ?? null,
                'message' => 'Limit order placed successfully',
                'data' => $order,
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to place limit order: " . $e->getMessage(), [
                'symbol' => $symbol,
                'direction' => $direction,
                'quantity' => $quantity,
                'price' => $price,
            ]);

            return [
                'success' => false,
                'order_id' => null,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    public function getCurrentPrice(string $symbol): ?float
    {
        try {
            $this->initializeExchange();
            $ticker = $this->exchange->fetchTicker($symbol);
            return $ticker['last'] ?? null;
        } catch (\Exception $e) {
            $this->logError("Failed to get current price: " . $e->getMessage(), ['symbol' => $symbol]);
            return null;
        }
    }

    public function getBalance(): array
    {
        try {
            $this->initializeExchange();
            $balance = $this->exchange->fetchBalance();
            
            return [
                'balance' => $balance['total'] ?? [],
                'equity' => $balance['total'] ?? [],
                'free_margin' => $balance['free'] ?? [],
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to get balance: " . $e->getMessage());
            return [
                'balance' => [],
                'equity' => [],
                'free_margin' => [],
            ];
        }
    }

    public function getOpenPositions(): array
    {
        try {
            $this->initializeExchange();
            
            // For spot trading, positions are just open orders
            // For futures/margin, use fetchPositions
            $positions = [];
            
            if (method_exists($this->exchange, 'fetchPositions')) {
                $positions = $this->exchange->fetchPositions();
            } else {
                // For spot exchanges, get open orders
                $orders = $this->exchange->fetchOpenOrders();
                // Convert orders to position-like format
                foreach ($orders as $order) {
                    $positions[] = [
                        'order_id' => $order['id'],
                        'symbol' => $order['symbol'],
                        'direction' => $order['side'],
                        'quantity' => $order['amount'],
                        'entry_price' => $order['price'] ?? $order['average'],
                        'current_price' => null,
                        'pnl' => 0,
                    ];
                }
            }

            return $positions;
        } catch (\Exception $e) {
            $this->logError("Failed to get open positions: " . $e->getMessage());
            return [];
        }
    }

    public function getPosition(string $orderId): ?array
    {
        try {
            $this->initializeExchange();
            $order = $this->exchange->fetchOrder($orderId);
            
            return [
                'order_id' => $order['id'],
                'symbol' => $order['symbol'],
                'direction' => $order['side'],
                'quantity' => $order['amount'],
                'entry_price' => $order['price'] ?? $order['average'],
                'current_price' => null,
                'pnl' => 0,
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to get position: " . $e->getMessage(), ['order_id' => $orderId]);
            return null;
        }
    }

    public function closePosition(string $orderId): array
    {
        try {
            $this->initializeExchange();
            
            // Cancel the order first
            $this->exchange->cancelOrder($orderId);
            
            // For spot, closing means canceling the order
            // For futures, it means closing the position
            if (method_exists($this->exchange, 'closePosition')) {
                $result = $this->exchange->closePosition($orderId);
            } else {
                $order = $this->exchange->fetchOrder($orderId);
                $result = [
                    'success' => true,
                    'close_price' => $order['price'] ?? $order['average'],
                ];
            }

            return [
                'success' => true,
                'close_price' => $result['close_price'] ?? null,
                'message' => 'Position closed successfully',
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to close position: " . $e->getMessage(), ['order_id' => $orderId]);
            return [
                'success' => false,
                'close_price' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function updateStopLoss(string $orderId, float $slPrice): array
    {
        // Most crypto exchanges don't support stop-loss orders directly
        // This would need to be implemented via conditional orders or monitoring
        return [
            'success' => false,
            'message' => 'Stop-loss update not directly supported. Use monitoring service instead.',
        ];
    }

    public function updateTakeProfit(string $orderId, float $tpPrice): array
    {
        // Most crypto exchanges don't support take-profit orders directly
        return [
            'success' => false,
            'message' => 'Take-profit update not directly supported. Use monitoring service instead.',
        ];
    }

    public function cancelOrder(string $orderId): array
    {
        try {
            $this->initializeExchange();
            $this->exchange->cancelOrder($orderId);
            
            return [
                'success' => true,
                'message' => 'Order cancelled successfully',
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to cancel order: " . $e->getMessage(), ['order_id' => $orderId]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}

