<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\MarketRouter\Services;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TradingHoursService
{
    /**
     * Check if market is open for trading.
     *
     * @param string $marketType 'crypto' or 'forex'
     * @param string $symbol Optional symbol to check specific trading hours
     * @param string $timezone Timezone for the check (default: UTC)
     * @return bool True if market is open, false otherwise
     */
    public function isOpen(string $marketType, ?string $symbol = null, string $timezone = 'UTC'): bool
    {
        // Crypto markets are 24/7
        if ($marketType === 'crypto') {
            return true;
        }
        
        // Forex markets have trading hours
        return $this->isForexOpenNow($symbol ?? 'default', $timezone);
    }
    
    /**
     * Get the next opening time for a market.
     *
     * @param string $marketType 'crypto' or 'forex'
     * @param string $symbol Optional symbol
     * @param string $timezone Timezone for the result
     * @return Carbon|null Null for crypto (always open), Carbon for forex opening time
     */
    public function getOpeningTime(string $marketType, ?string $symbol = null, string $timezone = 'UTC'): ?Carbon
    {
        // Crypto markets are 24/7
        if ($marketType === 'crypto') {
            return null;
        }
        
        return $this->getNextForexOpeningTime($symbol ?? 'default', $timezone);
    }
    
    /**
     * Get the next closing time for a market.
     *
     * @param string $marketType 'crypto' or 'forex'
     * @param string $symbol Optional symbol
     * @param string $timezone Timezone for the result
     * @return Carbon|null Null for crypto (always open), Carbon for forex closing time
     */
    public function getClosingTime(string $marketType, ?string $symbol = null, string $timezone = 'UTC'): ?Carbon
    {
        // Crypto markets are 24/7
        if ($marketType === 'crypto') {
            return null;
        }
        
        return $this->getNextForexClosingTime($symbol ?? 'default', $timezone);
    }
    
    /**
     * Check if forex market is currently open (with caching).
     */
    protected function isForexOpenNow(string $symbol, string $timezone): bool
    {
        // Use cache for performance (1 hour TTL)
        $cacheKey = "forex_hours:{$symbol}:{$timezone}:is_open";
        
        return Cache::remember($cacheKey, 3600, function () use ($timezone) {
            return $this->checkForexMarketStatus($timezone);
        });
    }
    
    /**
     * Get next forex opening time.
     */
    protected function getNextForexOpeningTime(string $symbol, string $timezone): ?Carbon
    {
        $now = Carbon::now($timezone);
        $dayOfWeek = $now->dayOfWeek;
        $hour = (int)$now->format('H');
        $minute = (int)$now->format('i');
        
        // Forex opens Monday at 22:00 UTC (equivalent to 5 PM EST Sunday)
        // Forex closes Friday at 22:00 UTC (equivalent to 5 PM EST Friday)
        
        // If it's currently before opening time today
        if ($dayOfWeek === Carbon::MONDAY && $hour < 22) {
            return $now->copy()->setHour(22)->setMinute(0)->setSecond(0);
        }
        
        // If it's currently after closing time today
        if ($dayOfWeek === Carbon::FRIDAY && $hour >= 22) {
            // Next opening is Monday
            return $now->copy()->addDays(7 - $dayOfWeek)->setHour(22)->setMinute(0)->setSecond(0);
        }
        
        // If it's the weekend
        if ($dayOfWeek === Carbon::SATURDAY || $dayOfWeek === Carbon::SUNDAY) {
            // Next opening is Monday
            $daysUntilMonday = Carbon::MONDAY - $dayOfWeek;
            return $now->copy()->addDays($daysUntilMonday)->setHour(22)->setMinute(0)->setSecond(0);
        }
        
        // It's a weekday during trading hours
        if ($dayOfWeek >= Carbon::MONDAY && $dayOfWeek <= Carbon::FRIDAY && $hour < 22) {
            return null; // Already open
        }
        
        // Friday after hours - next opening is Monday
        if ($dayOfWeek === Carbon::FRIDAY) {
            return $now->copy()->addDays(3)->setHour(22)->setMinute(0)->setSecond(0);
        }
        
        return null;
    }
    
    /**
     * Get next forex closing time.
     */
    protected function getNextForexClosingTime(string $symbol, string $timezone): ?Carbon
    {
        $now = Carbon::now($timezone);
        $dayOfWeek = $now->dayOfWeek;
        $hour = (int)$now->format('H');
        
        // Forex closes Friday at 22:00 UTC
        
        // If it's currently before closing time today
        if ($dayOfWeek === Carbon::FRIDAY && $hour < 22) {
            return $now->copy()->setHour(22)->setMinute(0)->setSecond(0);
        }
        
        // If it's currently after closing time today
        if ($dayOfWeek === Carbon::FRIDAY && $hour >= 22) {
            // Next closing is Friday (next week)
            return $now->copy()->addDays(7)->setHour(22)->setMinute(0)->setSecond(0);
        }
        
        // If it's the weekend
        if ($dayOfWeek === Carbon::SATURDAY) {
            // Next closing is Friday (2 days away)
            return $now->copy()->addDays(6)->setHour(22)->setMinute(0)->setSecond(0);
        }
        
        // If it's Sunday
        if ($dayOfWeek === Carbon::SUNDAY) {
            // Next closing is Friday (5 days away)
            return $now->copy()->addDays(5)->setHour(22)->setMinute(0)->setSecond(0);
        }
        
        // It's a weekday - check if we're before or after hours
        if ($dayOfWeek >= Carbon::MONDAY && $dayOfWeek <= Carbon::THURSDAY) {
            return $now->copy()->addDays(Carbon::FRIDAY - $dayOfWeek)->setHour(22)->setMinute(0)->setSecond(0);
        }
        
        return null;
    }
    
    /**
     * Check forex market status (simplified implementation).
     */
    protected function checkForexMarketStatus(string $timezone): bool
    {
        $now = Carbon::now($timezone);
        $dayOfWeek = $now->dayOfWeek;
        $hour = (int)$now->format('H');
        $minute = (int)$now->format('i');
        
        // Forex trading hours: Monday 22:00 UTC to Friday 22:00 UTC
        // Simplified: Open Monday-Friday, Closed Saturday-Sunday
        
        // Weekend closed
        if ($dayOfWeek === Carbon::SATURDAY || $dayOfWeek === Carbon::SUNDAY) {
            return false;
        }
        
        // Friday after 22:00 UTC closed
        if ($dayOfWeek === Carbon::FRIDAY && $hour >= 22) {
            return false;
        }
        
        // Monday before 22:00 UTC closed
        if ($dayOfWeek === Carbon::MONDAY && $hour < 22) {
            return false;
        }
        
        // Weekdays 22:00-24:00 open
        return $dayOfWeek >= Carbon::TUESDAY && $dayOfWeek <= Carbon::THURSDAY 
            || ($dayOfWeek === Carbon::FRIDAY && $hour < 22)
            || ($dayOfWeek === Carbon::MONDAY && $hour >= 22);
    }
}
