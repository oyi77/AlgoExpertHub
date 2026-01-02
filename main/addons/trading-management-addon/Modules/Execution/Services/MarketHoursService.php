<?php

namespace Addons\TradingManagement\Modules\Execution\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * MarketHoursService
 * 
 * Validates market hours and holidays
 */
class MarketHoursService
{
    /**
     * Market trading hours by market type
     */
    protected const MARKET_HOURS = [
        'forex' => [
            'open' => '00:00',
            'close' => '23:59',
            'timezone' => 'UTC',
            'closed_days' => ['Saturday', 'Sunday'],
        ],
        'crypto' => [
            'open' => '00:00',
            'close' => '23:59',
            'timezone' => 'UTC',
            'closed_days' => [], // Crypto markets are 24/7
        ],
        'stock' => [
            'open' => '09:30',
            'close' => '16:00',
            'timezone' => 'America/New_York',
            'closed_days' => ['Saturday', 'Sunday'],
        ],
        'commodity' => [
            'open' => '00:00',
            'close' => '23:59',
            'timezone' => 'UTC',
            'closed_days' => ['Saturday', 'Sunday'],
        ],
    ];

    /**
     * Major market holidays (US market holidays as example)
     */
    protected const MARKET_HOLIDAYS = [
        '2025-01-01', // New Year's Day
        '2025-01-20', // Martin Luther King Jr. Day
        '2025-02-17', // Presidents' Day
        '2025-04-18', // Good Friday
        '2025-05-26', // Memorial Day
        '2025-06-19', // Juneteenth
        '2025-07-04', // Independence Day
        '2025-09-01', // Labor Day
        '2025-11-27', // Thanksgiving
        '2025-12-25', // Christmas
        // Add more holidays as needed
    ];

    /**
     * Check if market is currently open
     * 
     * @param string $symbol Trading symbol
     * @param string|null $timezone Timezone (optional, defaults to market timezone)
     * @return array ['is_open' => bool, 'reason' => string|null, 'next_open' => Carbon|null]
     */
    public function isMarketOpen(string $symbol, ?string $timezone = null): array
    {
        $marketType = $this->getMarketType($symbol);
        $marketHours = self::MARKET_HOURS[$marketType] ?? self::MARKET_HOURS['forex'];
        
        $now = Carbon::now($timezone ?? $marketHours['timezone']);
        
        // Check if it's a closed day
        $dayOfWeek = $now->format('l');
        if (in_array($dayOfWeek, $marketHours['closed_days'])) {
            $nextOpen = $this->getNextTradingDay($now, $marketType);
            return [
                'is_open' => false,
                'reason' => "Market is closed on {$dayOfWeek}",
                'next_open' => $nextOpen,
            ];
        }
        
        // Check if it's a holiday
        if ($this->isHoliday($now, $marketType)) {
            $nextOpen = $this->getNextTradingDay($now, $marketType);
            return [
                'is_open' => false,
                'reason' => 'Market is closed for holiday',
                'next_open' => $nextOpen,
            ];
        }
        
        // Check trading hours (for stock markets)
        if ($marketType === 'stock') {
            $openTime = Carbon::parse($now->format('Y-m-d') . ' ' . $marketHours['open'], $marketHours['timezone']);
            $closeTime = Carbon::parse($now->format('Y-m-d') . ' ' . $marketHours['close'], $marketHours['timezone']);
            
            if ($now->lt($openTime) || $now->gt($closeTime)) {
                $nextOpen = $now->lt($openTime) ? $openTime : $this->getNextTradingDay($now, $marketType);
                return [
                    'is_open' => false,
                    'reason' => "Market is closed. Trading hours: {$marketHours['open']} - {$marketHours['close']}",
                    'next_open' => $nextOpen,
                ];
            }
        }
        
        return [
            'is_open' => true,
            'reason' => null,
            'next_open' => null,
        ];
    }

    /**
     * Get market hours for a symbol
     * 
     * @param string $symbol Trading symbol
     * @return array Market hours configuration
     */
    public function getMarketHours(string $symbol): array
    {
        $marketType = $this->getMarketType($symbol);
        return self::MARKET_HOURS[$marketType] ?? self::MARKET_HOURS['forex'];
    }

    /**
     * Check if date is a market holiday
     * 
     * @param Carbon $date Date to check
     * @param string $market Market type
     * @return bool True if holiday
     */
    public function isHoliday(Carbon $date, string $market): bool
    {
        // For now, use US market holidays
        // In production, this should be configurable per market
        $dateString = $date->format('Y-m-d');
        return in_array($dateString, self::MARKET_HOLIDAYS);
    }

    /**
     * Get next trading day
     * 
     * @param Carbon $date Starting date
     * @param string $market Market type
     * @return Carbon Next trading day
     */
    public function getNextTradingDay(Carbon $date, string $market): Carbon
    {
        $marketHours = self::MARKET_HOURS[$market] ?? self::MARKET_HOURS['forex'];
        $nextDay = $date->copy()->addDay();
        
        // Skip closed days
        while (in_array($nextDay->format('l'), $marketHours['closed_days']) || $this->isHoliday($nextDay, $market)) {
            $nextDay->addDay();
        }
        
        // For stock markets, set to market open time
        if ($market === 'stock') {
            $nextDay->setTimeFromTimeString($marketHours['open']);
        }
        
        return $nextDay;
    }

    /**
     * Get market type from symbol
     * 
     * @param string $symbol Trading symbol
     * @return string Market type
     */
    protected function getMarketType(string $symbol): string
    {
        $symbol = strtoupper($symbol);
        
        // Crypto
        if (str_contains($symbol, 'BTC') || str_contains($symbol, 'ETH') || 
            str_contains($symbol, 'USDT') || str_contains($symbol, 'USDC')) {
            return 'crypto';
        }
        
        // Commodities
        if (str_contains($symbol, 'XAU') || str_contains($symbol, 'XAG') || 
            str_contains($symbol, 'GOLD') || str_contains($symbol, 'SILVER') ||
            str_contains($symbol, 'OIL') || str_contains($symbol, 'CRUDE')) {
            return 'commodity';
        }
        
        // Stocks (simple heuristic - can be enhanced)
        if (preg_match('/^[A-Z]{1,5}$/', $symbol) && !str_contains($symbol, '/')) {
            return 'stock';
        }
        
        // Default to forex
        return 'forex';
    }
}

