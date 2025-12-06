<?php

namespace Addons\TradingExecutionEngine\App\Adapters;

use Addons\TradingExecutionEngine\App\Contracts\ExchangeAdapterInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class Mt4Adapter extends BaseExchangeAdapter implements ExchangeAdapterInterface
{
    protected Client $client;
    protected string $baseUrl;
    protected bool $connected = false;

    public function __construct(ExecutionConnection $connection)
    {
        parent::__construct($connection);
        $this->baseUrl = config('trading-execution-engine.mtapi.base_url', 'https://api.mtapi.io');
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function testConnection(array $credentials): array
    {
        try {
            $this->credentials = $credentials;
            
            if (empty($credentials['api_key']) || empty($credentials['account_id'])) {
                return [
                    'success' => false,
                    'message' => 'API key and account ID are required',
                    'data' => [],
                ];
            }

            $accountInfo = $this->getAccountInfo();
            $this->connected = true;

            return [
                'success' => true,
                'message' => sprintf(
                    'MT4 connection successful. Balance: %.2f %s',
                    $accountInfo['balance'] ?? 0,
                    $accountInfo['currency'] ?? 'USD'
                ),
                'data' => $accountInfo,
            ];
        } catch (\Exception $e) {
            $this->connected = false;
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
            $this->ensureConnected();

            $side = strtolower($direction) === 'buy' ? 'buy' : 'sell';
            $lotSize = $this->convertQuantityToLots($symbol, $quantity);

            $endpoint = sprintf('/v1/accounts/%s/orders', $this->credentials['account_id']);
            
            $orderData = [
                'symbol' => $symbol,
                'type' => 'market',
                'side' => $side,
                'volume' => $lotSize,
            ];

            // Add SL/TP if provided
            if (isset($options['sl_price'])) {
                $orderData['stop_loss'] = (float) $options['sl_price'];
            }
            if (isset($options['tp_price'])) {
                $orderData['take_profit'] = (float) $options['tp_price'];
            }

            $response = $this->client->post($endpoint, [
                'json' => $orderData,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['error'])) {
                throw new \Exception($data['message'] ?? 'Order placement failed');
            }

            return [
                'success' => true,
                'order_id' => (string) ($data['order_id'] ?? $data['ticket'] ?? null),
                'price' => (float) ($data['price'] ?? $data['open_price'] ?? 0),
                'message' => 'Order placed successfully',
                'data' => $data,
            ];
        } catch (\Exception $e) {
            $this->logError("MT4 market order failed: " . $e->getMessage());
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
            $this->ensureConnected();

            $side = strtolower($direction) === 'buy' ? 'buy' : 'sell';
            $lotSize = $this->convertQuantityToLots($symbol, $quantity);

            $endpoint = sprintf('/v1/accounts/%s/orders', $this->credentials['account_id']);
            
            $orderData = [
                'symbol' => $symbol,
                'type' => $side === 'buy' ? 'buy_limit' : 'sell_limit',
                'side' => $side,
                'volume' => $lotSize,
                'price' => $price,
            ];

            if (isset($options['sl_price'])) {
                $orderData['stop_loss'] = (float) $options['sl_price'];
            }
            if (isset($options['tp_price'])) {
                $orderData['take_profit'] = (float) $options['tp_price'];
            }

            $response = $this->client->post($endpoint, [
                'json' => $orderData,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['error'])) {
                throw new \Exception($data['message'] ?? 'Limit order placement failed');
            }

            return [
                'success' => true,
                'order_id' => (string) ($data['order_id'] ?? $data['ticket'] ?? null),
                'message' => 'Limit order placed successfully',
                'data' => $data,
            ];
        } catch (\Exception $e) {
            $this->logError("MT4 limit order failed: " . $e->getMessage());
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
            $this->ensureConnected();

            $endpoint = sprintf('/v1/accounts/%s/symbols/%s/quote', $this->credentials['account_id'], $symbol);

            $response = $this->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['bid']) && isset($data['ask'])) {
                return (float) (($data['bid'] + $data['ask']) / 2);
            }

            return (float) ($data['price'] ?? null);
        } catch (\Exception $e) {
            $this->logError("Failed to get MT4 price: " . $e->getMessage());
            return null;
        }
    }

    public function getBalance(): array
    {
        try {
            $accountInfo = $this->getAccountInfo();
            return [
                'balance' => (float) ($accountInfo['balance'] ?? 0),
                'equity' => (float) ($accountInfo['equity'] ?? 0),
                'free_margin' => (float) ($accountInfo['free_margin'] ?? 0),
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to get MT4 balance: " . $e->getMessage());
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
            $this->ensureConnected();

            $endpoint = sprintf('/v1/accounts/%s/positions', $this->credentials['account_id']);

            $response = $this->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $positions = $data['positions'] ?? $data['data'] ?? [];

            $normalized = [];
            foreach ($positions as $pos) {
                $normalized[] = [
                    'order_id' => (string) ($pos['ticket'] ?? $pos['order_id'] ?? ''),
                    'symbol' => $pos['symbol'] ?? '',
                    'direction' => strtolower($pos['type'] ?? 'buy') === 'buy' ? 'buy' : 'sell',
                    'quantity' => (float) ($pos['volume'] ?? 0),
                    'entry_price' => (float) ($pos['open_price'] ?? 0),
                    'current_price' => (float) ($pos['current_price'] ?? 0),
                    'pnl' => (float) ($pos['profit'] ?? 0),
                    'sl_price' => isset($pos['stop_loss']) ? (float) $pos['stop_loss'] : null,
                    'tp_price' => isset($pos['take_profit']) ? (float) $pos['take_profit'] : null,
                ];
            }

            return $normalized;
        } catch (\Exception $e) {
            $this->logError("Failed to get MT4 positions: " . $e->getMessage());
            return [];
        }
    }

    public function getPosition(string $orderId): ?array
    {
        $positions = $this->getOpenPositions();
        foreach ($positions as $pos) {
            if ($pos['order_id'] === (string) $orderId) {
                return $pos;
            }
        }
        return null;
    }

    public function closePosition(string $orderId): array
    {
        try {
            $this->ensureConnected();

            $endpoint = sprintf('/v1/accounts/%s/positions/%s/close', $this->credentials['account_id'], $orderId);

            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['error'])) {
                throw new \Exception($data['message'] ?? 'Position close failed');
            }

            return [
                'success' => true,
                'close_price' => (float) ($data['close_price'] ?? 0),
                'message' => 'Position closed successfully',
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to close MT4 position: " . $e->getMessage());
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
            $this->ensureConnected();

            $endpoint = sprintf('/v1/accounts/%s/positions/%s/modify', $this->credentials['account_id'], $orderId);

            $response = $this->client->put($endpoint, [
                'json' => ['stop_loss' => $slPrice],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['error'])) {
                throw new \Exception($data['message'] ?? 'Stop loss update failed');
            }

            return [
                'success' => true,
                'message' => 'Stop loss updated successfully',
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to update MT4 stop loss: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function updateTakeProfit(string $orderId, float $tpPrice): array
    {
        try {
            $this->ensureConnected();

            $endpoint = sprintf('/v1/accounts/%s/positions/%s/modify', $this->credentials['account_id'], $orderId);

            $response = $this->client->put($endpoint, [
                'json' => ['take_profit' => $tpPrice],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['error'])) {
                throw new \Exception($data['message'] ?? 'Take profit update failed');
            }

            return [
                'success' => true,
                'message' => 'Take profit updated successfully',
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to update MT4 take profit: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function cancelOrder(string $orderId): array
    {
        try {
            $this->ensureConnected();

            $endpoint = sprintf('/v1/accounts/%s/orders/%s', $this->credentials['account_id'], $orderId);

            $response = $this->client->delete($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['error'])) {
                throw new \Exception($data['message'] ?? 'Order cancellation failed');
            }

            return [
                'success' => true,
                'message' => 'Order cancelled successfully',
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to cancel MT4 order: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function getAccountInfo(): array
    {
        $endpoint = sprintf('/v1/accounts/%s', $this->credentials['account_id']);

        $response = $this->client->get($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->credentials['api_key'],
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data['error'])) {
            throw new \Exception($data['message'] ?? 'Failed to fetch account info');
        }

        return [
            'balance' => $data['balance'] ?? 0,
            'equity' => $data['equity'] ?? 0,
            'margin' => $data['margin'] ?? 0,
            'free_margin' => $data['free_margin'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
        ];
    }

    protected function ensureConnected(): void
    {
        if (!$this->connected) {
            $this->testConnection($this->credentials);
        }
    }

    protected function convertQuantityToLots(string $symbol, float $quantity): float
    {
        // MT4 uses lot sizes (standard lot = 100,000 units)
        // Convert quantity to lots
        // For most FX pairs, 1 lot = 100,000 units
        // This is a simplified conversion - actual lot size may vary by broker
        return round($quantity / 100000, 2);
    }
}
