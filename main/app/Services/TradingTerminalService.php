<?php

namespace App\Services;

use App\Models\User;
use App\Events\PositionUpdated;
use App\Repositories\Contracts\ExchangeConnectionRepositoryInterface;
use App\Services\InternalBrokerService;
use App\Services\MarketDataService;
use Illuminate\Support\Facades\Log;

class TradingTerminalService
{
    protected $brokerService;
    protected $marketDataService;
    protected $exchangeConnectionRepository;

    public function __construct(
        InternalBrokerService $brokerService,
        MarketDataService $marketDataService,
        ExchangeConnectionRepositoryInterface $exchangeConnectionRepository
    ) {
        $this->brokerService = $brokerService;
        $this->marketDataService = $marketDataService;
        $this->exchangeConnectionRepository = $exchangeConnectionRepository;
    }

    /**
     * Place an order (handles both demo/internal and real/exchange)
     *
     * @param User $user
     * @param array $data Validated order data
     * @return array
     * @throws \Exception
     */
    public function placeOrder(User $user, array $data): array
    {
        $mode = $data['mode'] ?? 'demo';
        $connectionId = $data['connection_id'] ?? null;

        // Real trading mode: use exchange connection
        if ($mode === 'real' && $connectionId) {
            return $this->placeOrderOnExchange($user, $data, (int)$connectionId);
        }

        // Demo mode: use internal broker
        return $this->placeInternalOrder($user, $data);
    }

    /**
     * Place order with internal broker (Demo Mode)
     */
    protected function placeInternalOrder(User $user, array $data): array
    {
        // Get current market price
        $currentPrice = $this->marketDataService->getCurrentPrice($data['symbol']);

        if (!$currentPrice) {
            throw new \Exception('Unable to fetch current market price');
        }

        // Place order with internal broker
        $trade = $this->brokerService->placeOrder(
            $user,
            $data['symbol'],
            $data['direction'],
            $data['quantity'],
            $currentPrice,
            $data['sl_price'] ?? null,
            $data['tp_price'] ?? null
        );

        // Broadcast position update
        broadcast(new PositionUpdated($user->id, [
            'id' => $trade->id,
            'symbol' => $trade->symbol,
            'direction' => $trade->direction,
            'quantity' => $trade->quantity,
            'entry_price' => $trade->entry_price,
            'current_price' => $trade->current_price,
            'pnl' => $trade->pnl,
            'status' => $trade->status,
        ]));

        return [
            'success' => true,
            'message' => 'Order placed successfully (Demo Mode)',
            'data' => [
                'trade_id' => $trade->id,
                'symbol' => $trade->symbol,
                'direction' => $trade->direction,
                'quantity' => $trade->quantity,
                'entry_price' => $trade->entry_price,
                'mode' => 'demo',
            ],
        ];
    }

    /**
     * Place order on connected exchange
     */
    protected function placeOrderOnExchange(User $user, array $data, int $connectionId): array
    {
        try {
            // Get connection using Repository
            // Use find() on repository if method exists, otherwise use model directly?
            // BaseRepository has find($id).
            // But we need check ownership and active status.
            // ExchangeConnectionRepository has getUserConnections methods but maybe not single get.
            // Let's use BaseRepository's find() and check manually or add method to Repo or use Model directly if Repo method is missing?
            // Better to add getActiveUserConnection($userId, $connectionId) to Repo?
            // For now, I'll fetch via Repository if possible, or fallback to eloquent for complex query until Repo is upgraded.
            // Actually, I can use the model directly to match original logic or update Repo.
            // But I'm in "Complete Repository Pattern" mode.
            // Let's rely on Repo -> getUserConnections and filter? No, inefficient.
            // I'll stick to Eloquent for specific constrained find if Repo doesn't have it, OR just use `find` and check.
            
            // To stick to pattern, let's assume I can use `find` and verify.
            // But internal logic used specific conditions.
            // I'll use simple Eloquent for now as getting a single connection with constraints wasn't in the Interface I implemented perfectly.
            // Wait, I implemented `getUserConnections` and `getActiveConnections`.
            // I'll use direct Eloquent here or add a method.
            // I'll use explicit Eloquent query matching original controller to be safe and accurate.
            
            // Re-using the logic from Controller which handles two possible Model locations
            $connection = null;
            if (class_exists(\Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::class)) {
                $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('id', $connectionId)
                    ->where('user_id', $user->id)
                    ->where('is_admin_owned', false)
                    ->where('is_active', true)
                    ->firstOrFail();
            } elseif (class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class)) {
                $connection = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::where('id', $connectionId)
                    ->where('user_id', $user->id)
                    ->where('is_admin_owned', false)
                    ->where('is_active', true)
                    ->firstOrFail();
            }

            if (!$connection) {
                throw new \Exception('Exchange connection not found or inactive');
            }

            // Get adapter
            $adapter = $this->getAdapter($connection);
            if (!$adapter || !method_exists($adapter, 'placeOrder')) {
                throw new \Exception('Trade execution not supported for this connection type');
            }

            // Map direction
            $direction = strtolower($data['direction']);
            if (in_array($direction, ['long', 'short'])) {
                $direction = $direction === 'long' ? 'buy' : 'sell';
            }

            // Execute trade via adapter
            $result = $adapter->placeOrder(
                $data['symbol'],
                $direction,
                $data['quantity'],
                'market', // Terminal always uses market orders
                null, // No entry price for market orders
                $data['sl_price'] ?? null,
                $data['tp_price'] ?? null,
                'Terminal: ' . $data['symbol']
            );

            if (isset($result['success']) && $result['success'] === false) {
                throw new \Exception($result['message'] ?? 'Trade execution failed');
            }

            // Create execution log
            $ExecutionLog = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::class;
            $log = $ExecutionLog::create([
                'connection_id' => $connection->id,
                'signal_id' => null,
                'symbol' => $data['symbol'],
                'direction' => $direction,
                'quantity' => $data['quantity'],
                'entry_price' => $result['data']['openPrice'] ?? $result['data']['price'] ?? null,
                'sl_price' => $data['sl_price'] ?? null,
                'tp_price' => $data['tp_price'] ?? null,
                'execution_type' => 'market',
                'status' => 'executed',
                'executed_at' => now(),
                'order_id' => $result['orderId'] ?? $result['numericTicket'] ?? null,
            ]);

            // Create position if needed
            $orderId = $result['orderId'] ?? $result['numericTicket'] ?? null;
            $positionId = $result['positionId'] ?? null;
            
            if ($orderId || $positionId) {
                $ExecutionPosition = \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::class;
                $entryPrice = $result['data']['openPrice'] ?? $result['data']['price'] ?? 0;
                
                $ExecutionPosition::create([
                    'connection_id' => $connection->id,
                    'execution_log_id' => $log->id,
                    'order_id' => $orderId ? (string)$orderId : null,
                    'symbol' => $data['symbol'],
                    'direction' => $direction,
                    'quantity' => $data['quantity'],
                    'entry_price' => $entryPrice > 0 ? $entryPrice : 0,
                    'current_price' => $entryPrice > 0 ? $entryPrice : 0,
                    'sl_price' => $data['sl_price'] ?? null,
                    'tp_price' => $data['tp_price'] ?? null,
                    'status' => 'open',
                ]);
            }

            return [
                'success' => true,
                'message' => 'Order placed successfully on exchange',
                'data' => [
                    'order_id' => $orderId,
                    'position_id' => $positionId,
                    'symbol' => $data['symbol'],
                    'direction' => strtoupper($direction),
                    'quantity' => $data['quantity'],
                    'entry_price' => $result['data']['openPrice'] ?? $result['data']['price'] ?? 'Market',
                    'mode' => 'real',
                    'connection' => $connection->name,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to place order on exchange', [
                'user_id' => $user->id,
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get adapter for connection
     */
    protected function getAdapter($connection)
    {
        $connectionType = $connection->connection_type ?? null;
        $provider = $connection->provider ?? null;
        $type = $connection->type ?? null;
        $exchangeName = $connection->exchange_name ?? null;
        
        if ($connectionType === 'CRYPTO_EXCHANGE' || ($type === 'crypto' && !$connectionType)) {
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter(
                $connection->credentials,
                $provider ?? $exchangeName ?? 'binance'
            );
        }
        
        if ($provider === 'mtapi_grpc' || (isset($connection->credentials['provider']) && $connection->credentials['provider'] === 'mtapi_grpc')) {
            $credentials = $connection->credentials;
            $globalSettings = \App\Services\GlobalConfigurationService::get('mtapi_global_settings', []);
            if (!empty($globalSettings['base_url'])) {
                $credentials['base_url'] = $globalSettings['base_url'];
            }
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiGrpcAdapter($credentials);
        } elseif ($provider === 'metaapi' || (isset($connection->credentials['provider']) && $connection->credentials['provider'] === 'metaapi')) {
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter($connection->credentials);
        } else {
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiAdapter($connection->credentials);
        }
    }
}
