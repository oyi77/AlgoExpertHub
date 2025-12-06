<?php

namespace Addons\TradingExecutionEngine\App\Adapters;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FxBrokerAdapter extends BaseExchangeAdapter
{
    protected string $baseUrl;

    public function __construct($connection)
    {
        parent::__construct($connection);
        
        // mtapi.io base URLs - determine from exchange_name or connection type
        $exchangeName = strtolower($this->connection->exchange_name);
        if (strpos($exchangeName, 'mt4') !== false || $exchangeName === 'mt4') {
            $this->baseUrl = 'https://mt4.mtapi.io';
        } else {
            $this->baseUrl = 'https://mt5.mtapi.io';
        }
    }

    public function getExchangeName(): string
    {
        return $this->connection->exchange_name;
    }

    public function validateCredentials(array $credentials): bool
    {
        return isset($credentials['api_key']) && isset($credentials['api_secret']) && isset($credentials['account_id']);
    }

    /**
     * Make authenticated API request to mtapi.io.
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $credentials = $this->credentials;
            $apiKey = $credentials['api_key'] ?? '';
            $apiSecret = $credentials['api_secret'] ?? '';
            $accountId = $credentials['account_id'] ?? '';

            $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
                'X-API-Secret' => $apiSecret,
                'X-Account-Id' => $accountId,
            ])->{strtolower($method)}($url, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'data' => [],
                'message' => $response->body(),
            ];
        } catch (\Exception $e) {
            $this->logError("API request failed: " . $e->getMessage(), [
                'method' => $method,
                'endpoint' => $endpoint,
            ]);
            return [
                'success' => false,
                'data' => [],
                'message' => $e->getMessage(),
            ];
        }
    }

    public function testConnection(array $credentials): array
    {
        try {
            $this->credentials = $credentials;
            $result = $this->makeRequest('GET', '/account');

            if ($result['success']) {
                $this->connected = true;
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'data' => $result['data'],
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Connection failed',
                'data' => [],
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
            $direction = strtolower($direction);
            $type = $direction === 'buy' ? 0 : 1; // 0 = buy, 1 = sell for MT4/MT5

            $data = [
                'symbol' => $symbol,
                'type' => $type,
                'volume' => $quantity,
                'price' => 0, // Market order
            ];

            if (isset($options['sl_price'])) {
                $data['sl'] = $options['sl_price'];
            }

            if (isset($options['tp_price'])) {
                $data['tp'] = $options['tp_price'];
            }

            $result = $this->makeRequest('POST', '/order', $data);

            if ($result['success']) {
                $orderData = $result['data'];
                return [
                    'success' => true,
                    'order_id' => $orderData['ticket'] ?? $orderData['order'] ?? null,
                    'price' => $orderData['price'] ?? $orderData['open_price'] ?? null,
                    'message' => 'Order placed successfully',
                    'data' => $orderData,
                ];
            }

            return [
                'success' => false,
                'order_id' => null,
                'price' => null,
                'message' => $result['message'] ?? 'Failed to place order',
                'data' => [],
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
            $direction = strtolower($direction);
            $type = $direction === 'buy' ? 2 : 3; // 2 = buy limit, 3 = sell limit

            $data = [
                'symbol' => $symbol,
                'type' => $type,
                'volume' => $quantity,
                'price' => $price,
            ];

            if (isset($options['sl_price'])) {
                $data['sl'] = $options['sl_price'];
            }

            if (isset($options['tp_price'])) {
                $data['tp'] = $options['tp_price'];
            }

            $result = $this->makeRequest('POST', '/order', $data);

            if ($result['success']) {
                $orderData = $result['data'];
                return [
                    'success' => true,
                    'order_id' => $orderData['ticket'] ?? $orderData['order'] ?? null,
                    'message' => 'Limit order placed successfully',
                    'data' => $orderData,
                ];
            }

            return [
                'success' => false,
                'order_id' => null,
                'message' => $result['message'] ?? 'Failed to place limit order',
                'data' => [],
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to place limit order: " . $e->getMessage());
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
            $result = $this->makeRequest('GET', '/quote', ['symbol' => $symbol]);
            
            if ($result['success'] && isset($result['data']['bid'])) {
                // Use bid price for sell, ask for buy - average for simplicity
                $bid = $result['data']['bid'] ?? 0;
                $ask = $result['data']['ask'] ?? 0;
                return ($bid + $ask) / 2;
            }

            return null;
        } catch (\Exception $e) {
            $this->logError("Failed to get current price: " . $e->getMessage(), ['symbol' => $symbol]);
            return null;
        }
    }

    public function getBalance(): array
    {
        try {
            $result = $this->makeRequest('GET', '/account');

            if ($result['success']) {
                $account = $result['data'];
                return [
                    'balance' => $account['balance'] ?? 0,
                    'equity' => $account['equity'] ?? 0,
                    'free_margin' => $account['free_margin'] ?? 0,
                ];
            }

            return [
                'balance' => 0,
                'equity' => 0,
                'free_margin' => 0,
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to get balance: " . $e->getMessage());
            return [
                'balance' => 0,
                'equity' => 0,
                'free_margin' => 0,
            ];
        }
    }

    public function getOpenPositions(): array
    {
        try {
            $result = $this->makeRequest('GET', '/positions');

            if ($result['success']) {
                $positions = [];
                foreach ($result['data'] ?? [] as $pos) {
                    $positions[] = [
                        'order_id' => $pos['ticket'] ?? $pos['order'] ?? null,
                        'symbol' => $pos['symbol'] ?? '',
                        'direction' => ($pos['type'] ?? 0) === 0 ? 'buy' : 'sell',
                        'quantity' => $pos['volume'] ?? 0,
                        'entry_price' => $pos['open_price'] ?? 0,
                        'current_price' => $pos['current_price'] ?? null,
                        'pnl' => $pos['profit'] ?? 0,
                    ];
                }
                return $positions;
            }

            return [];
        } catch (\Exception $e) {
            $this->logError("Failed to get open positions: " . $e->getMessage());
            return [];
        }
    }

    public function getPosition(string $orderId): ?array
    {
        try {
            $result = $this->makeRequest('GET', '/position', ['ticket' => $orderId]);

            if ($result['success'] && isset($result['data'])) {
                $pos = $result['data'];
                return [
                    'order_id' => $pos['ticket'] ?? $pos['order'] ?? null,
                    'symbol' => $pos['symbol'] ?? '',
                    'direction' => ($pos['type'] ?? 0) === 0 ? 'buy' : 'sell',
                    'quantity' => $pos['volume'] ?? 0,
                    'entry_price' => $pos['open_price'] ?? 0,
                    'current_price' => $pos['current_price'] ?? null,
                    'pnl' => $pos['profit'] ?? 0,
                ];
            }

            return null;
        } catch (\Exception $e) {
            $this->logError("Failed to get position: " . $e->getMessage(), ['order_id' => $orderId]);
            return null;
        }
    }

    public function closePosition(string $orderId): array
    {
        try {
            $result = $this->makeRequest('POST', '/close', ['ticket' => $orderId]);

            if ($result['success']) {
                $data = $result['data'];
                return [
                    'success' => true,
                    'close_price' => $data['close_price'] ?? null,
                    'message' => 'Position closed successfully',
                ];
            }

            return [
                'success' => false,
                'close_price' => null,
                'message' => $result['message'] ?? 'Failed to close position',
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
        try {
            $result = $this->makeRequest('POST', '/modify', [
                'ticket' => $orderId,
                'sl' => $slPrice,
            ]);

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Stop loss updated successfully',
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to update stop loss',
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to update stop loss: " . $e->getMessage(), ['order_id' => $orderId]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function updateTakeProfit(string $orderId, float $tpPrice): array
    {
        try {
            $result = $this->makeRequest('POST', '/modify', [
                'ticket' => $orderId,
                'tp' => $tpPrice,
            ]);

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Take profit updated successfully',
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to update take profit',
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to update take profit: " . $e->getMessage(), ['order_id' => $orderId]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function cancelOrder(string $orderId): array
    {
        try {
            $result = $this->makeRequest('POST', '/cancel', ['ticket' => $orderId]);

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Order cancelled successfully',
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to cancel order',
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

