<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\PaperTrading\Services;

use Addons\TradingManagement\Modules\PaperTrading\Models\VirtualPortfolio;
use Addons\TradingManagement\Modules\PaperTrading\Models\VirtualTrade;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use App\Models\User;
use App\Services\InternalBrokerService;
use Illuminate\Support\Facades\Log;

class PaperTradingService
{
    /**
     * Default virtual portfolio balance.
     */
    private const DEFAULT_INITIAL_BALANCE = 10000.0;

    public function __construct(
        private InternalBrokerService $internalBrokerService
    ) {}

    /**
     * Execute a paper trade with virtual portfolio.
     *
     * @param User $user The user executing the trade
     * @param ExecutionConnection $connection The exchange connection
     * @param string $symbol Trading symbol (e.g., BTC/USDT)
     * @param string $direction Trade direction (buy/sell/long/short)
     * @param float $quantity Trade quantity
     * @param float|null $entryPrice Entry price (optional, null for market orders)
     * @param float|null $stopLoss Stop loss price
     * @param float|null $takeProfit Take profit price
     * @return array Result with success status and trade details
     */
    public function executeTrade(
        User $user,
        ExecutionConnection $connection,
        string $symbol,
        string $direction,
        float $quantity,
        ?float $entryPrice = null,
        ?float $stopLoss = null,
        ?float $takeProfit = null
    ): array {
        // Get or create virtual portfolio
        $portfolio = $this->getOrCreatePortfolio($user, $connection);

        // Calculate trade cost
        $cost = $quantity * ($entryPrice ?? 0);

        // Check balance for buy/long trades
        if ($this->isBuyDirection($direction)) {
            if (!$portfolio->hasSufficientBalance($cost)) {
                Log::warning('Paper trade rejected: insufficient virtual balance', [
                    'user_id' => $user->id,
                    'symbol' => $symbol,
                    'required' => $cost,
                    'available' => $portfolio->current_balance,
                ]);

                return [
                    'success' => false,
                    'message' => 'Insufficient virtual balance',
                    'required' => $cost,
                    'available' => $portfolio->current_balance,
                ];
            }

            // Deduct from virtual portfolio
            $portfolio->updateBalance($cost, 'debit');
        }

        try {
            // Create internal trade record via InternalBrokerService with isPaper=true
            $internalTrade = $this->internalBrokerService->placeOrder(
                $user,
                $symbol,
                $direction,
                $quantity,
                $entryPrice ?? 0,
                $stopLoss,
                $takeProfit,
                true // isPaper=true - force paper mode
            );

            // Create virtual trade record
            $virtualTrade = $this->createVirtualTrade($portfolio, $internalTrade, $direction, $quantity);

            Log::info('Paper trade executed successfully', [
                'user_id' => $user->id,
                'trade_id' => $internalTrade->id ?? null,
                'virtual_trade_id' => $virtualTrade->id,
                'symbol' => $symbol,
                'direction' => $direction,
                'quantity' => $quantity,
                'virtual_balance' => $portfolio->current_balance,
            ]);

            return [
                'success' => true,
                'trade_id' => $internalTrade->id ?? null,
                'virtual_trade_id' => $virtualTrade->id,
                'virtual_balance' => $portfolio->current_balance,
                'pnl' => $portfolio->pnl,
                'pnl_percentage' => $portfolio->pnl_percentage,
            ];
        } catch (\Exception $e) {
            // Rollback portfolio balance if trade failed
            if ($this->isBuyDirection($direction)) {
                $portfolio->updateBalance($cost, 'credit');
            }

            Log::error('Paper trade execution failed', [
                'user_id' => $user->id,
                'symbol' => $symbol,
                'direction' => $direction,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Trade execution failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Close a paper trade (virtual position).
     *
     * @param User $user The user closing the trade
     * @param ExecutionConnection $connection The exchange connection
     * @param int $virtualTradeId The virtual trade to close
     * @param float|null $closePrice Price to close at (optional)
     * @return array Result with success status
     */
    public function closeTrade(
        User $user,
        ExecutionConnection $connection,
        int $virtualTradeId,
        ?float $closePrice = null
    ): array {
        $portfolio = $this->getOrCreatePortfolio($user, $connection);
        $virtualTrade = VirtualTrade::where('id', $virtualTradeId)
            ->where('virtual_portfolio_id', $portfolio->id)
            ->firstOrFail();

        if ($virtualTrade->status === 'closed') {
            return [
                'success' => false,
                'message' => 'Trade is already closed',
            ];
        }

        // Calculate profit/loss
        $closePrice = $closePrice ?? $virtualTrade->entry_price;
        $pnl = $this->calculateTradePnL($virtualTrade, $closePrice);

        // Credit/debit portfolio
        $direction = $virtualTrade->direction;

        if ($this->isBuyDirection($direction)) {
            // For buy trades, we close by selling - get back quantity * close price
            $portfolio->updateBalance($virtualTrade->quantity * $closePrice, 'credit');
        } else {
            // For sell trades, we close by buying - pay quantity * close price
            $portfolio->updateBalance($virtualTrade->quantity * $closePrice, 'debit');
        }

        // Update virtual trade
        $virtualTrade->update([
            'close_price' => $closePrice,
            'close_date' => now(),
            'pnl' => $pnl,
            'status' => 'closed',
        ]);

        Log::info('Paper trade closed', [
            'user_id' => $user->id,
            'virtual_trade_id' => $virtualTradeId,
            'close_price' => $closePrice,
            'pnl' => $pnl,
            'virtual_balance' => $portfolio->current_balance,
        ]);

        return [
            'success' => true,
            'virtual_trade_id' => $virtualTradeId,
            'close_price' => $closePrice,
            'pnl' => $pnl,
            'virtual_balance' => $portfolio->current_balance,
        ];
    }

    /**
     * Get virtual portfolio for user and connection.
     *
     * @param User $user The user
     * @param ExecutionConnection $connection The exchange connection
     * @return VirtualPortfolio
     */
    public function getPortfolio(User $user, ExecutionConnection $connection): VirtualPortfolio
    {
        return VirtualPortfolio::firstOrCreate(
            [
                'user_id' => $user->id,
                'exchange_connection_id' => $connection->id,
            ],
            [
                'balance' => self::DEFAULT_INITIAL_BALANCE,
                'initial_balance' => self::DEFAULT_INITIAL_BALANCE,
                'current_balance' => self::DEFAULT_INITIAL_BALANCE,
                'market_type' => $connection->type ?? 'crypto',
                'currency' => $this->getCurrencyFromSymbol($connection->type ?? 'crypto'),
                'is_active' => true,
            ]
        );
    }

    /**
     * Get virtual balance for user and connection.
     *
     * @param User $user The user
     * @param ExecutionConnection $connection The exchange connection
     * @return float Current virtual balance
     */
    public function getBalance(User $user, ExecutionConnection $connection): float
    {
        $portfolio = $this->getPortfolio($user, $connection);
        return $portfolio->current_balance;
    }

    /**
     * Reset virtual portfolio to initial balance.
     *
     * @param User $user The user
     * @param ExecutionConnection $connection The exchange connection
     * @param float|null $newBalance New balance (optional, defaults to initial)
     * @return VirtualPortfolio
     */
    public function resetPortfolio(User $user, ExecutionConnection $connection, ?float $newBalance = null): VirtualPortfolio
    {
        $balance = $newBalance ?? self::DEFAULT_INITIAL_BALANCE;

        $portfolio = $this->getOrCreatePortfolio($user, $connection);
        $portfolio->update([
            'balance' => $balance,
            'initial_balance' => $balance,
            'current_balance' => $balance,
            'pnl' => 0,
            'pnl_percentage' => 0,
        ]);

        Log::info('Virtual portfolio reset', [
            'user_id' => $user->id,
            'exchange_connection_id' => $connection->id,
            'new_balance' => $balance,
        ]);

        return $portfolio->fresh();
    }

    /**
     * Get all open virtual trades for a portfolio.
     *
     * @param User $user The user
     * @param ExecutionConnection $connection The exchange connection
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOpenTrades(User $user, ExecutionConnection $connection)
    {
        $portfolio = $this->getOrCreatePortfolio($user, $connection);

        return VirtualTrade::where('virtual_portfolio_id', $portfolio->id)
            ->where('status', 'open')
            ->get();
    }

    /**
     * Get portfolio summary including PnL and trade statistics.
     *
     * @param User $user The user
     * @param ExecutionConnection $connection The exchange connection
     * @return array
     */
    public function getPortfolioSummary(User $user, ExecutionConnection $connection): array
    {
        $portfolio = $this->getOrCreatePortfolio($user, $connection);

        $openTrades = VirtualTrade::where('virtual_portfolio_id', $portfolio->id)
            ->where('status', 'open')
            ->count();

        $closedTrades = VirtualTrade::where('virtual_portfolio_id', $portfolio->id)
            ->where('status', 'closed')
            ->count();

        $totalPnL = VirtualTrade::where('virtual_portfolio_id', $portfolio->id)
            ->where('status', 'closed')
            ->sum('pnl');

        return [
            'portfolio_id' => $portfolio->id,
            'balance' => $portfolio->current_balance,
            'initial_balance' => $portfolio->initial_balance,
            'pnl' => $portfolio->pnl,
            'pnl_percentage' => $portfolio->pnl_percentage,
            'open_trades' => $openTrades,
            'closed_trades' => $closedTrades,
            'total_closed_pnl' => $totalPnL,
            'market_type' => $portfolio->market_type,
        ];
    }

    /**
     * Get or create virtual portfolio.
     */
    private function getOrCreatePortfolio(User $user, ExecutionConnection $connection): VirtualPortfolio
    {
        return VirtualPortfolio::firstOrCreate(
            [
                'user_id' => $user->id,
                'exchange_connection_id' => $connection->id,
            ],
            [
                'balance' => self::DEFAULT_INITIAL_BALANCE,
                'initial_balance' => self::DEFAULT_INITIAL_BALANCE,
                'current_balance' => self::DEFAULT_INITIAL_BALANCE,
                'market_type' => $connection->type ?? 'crypto',
                'currency' => $this->getCurrencyFromSymbol($connection->type ?? 'crypto'),
                'is_active' => true,
            ]
        );
    }

    /**
     * Create virtual trade record.
     */
    private function createVirtualTrade(
        VirtualPortfolio $portfolio,
        $internalTrade,
        string $direction,
        float $quantity
    ): VirtualTrade {
        return VirtualTrade::create([
            'virtual_portfolio_id' => $portfolio->id,
            'internal_trade_id' => $internalTrade->id ?? null,
            'symbol' => $internalTrade->symbol ?? 'unknown',
            'direction' => $direction,
            'quantity' => $quantity,
            'entry_price' => $internalTrade->entry_price ?? 0,
            'stop_loss' => $internalTrade->sl ?? null,
            'take_profit' => $internalTrade->tp ?? null,
            'open_date' => now(),
            'status' => 'open',
            'pnl' => 0,
        ]);
    }

    /**
     * Check if direction is buy/long.
     */
    private function isBuyDirection(string $direction): bool
    {
        return in_array(strtolower($direction), ['buy', 'long']);
    }

    /**
     * Calculate PnL for a trade.
     */
    private function calculateTradePnL(VirtualTrade $trade, float $closePrice): float
    {
        $direction = strtolower($trade->direction);
        $entryPrice = $trade->entry_price;
        $quantity = $trade->quantity;

        if ($direction === 'buy' || $direction === 'long') {
            return ($closePrice - $entryPrice) * $quantity;
        } else {
            return ($entryPrice - $closePrice) * $quantity;
        }
    }

    /**
     * Get currency from market type.
     */
    private function getCurrencyFromSymbol(string $marketType): string
    {
        return match ($marketType) {
            'crypto' => 'USDT',
            'fx' => 'USD',
            default => 'USD',
        };
    }
}
