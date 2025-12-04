<?php

namespace Addons\TradingManagement\Modules\MarketData\Models;

use Addons\TradingManagement\Modules\DataProvider\Models\DataConnection;
use Illuminate\Database\Eloquent\Model;

/**
 * MarketData Model
 * 
 * Stores OHLCV (candlestick) data from all providers
 * 
 * @property int $id
 * @property int $data_connection_id
 * @property string $symbol
 * @property string $timeframe
 * @property int $timestamp
 * @property float $open
 * @property float $high
 * @property float $low
 * @property float $close
 * @property float|null $volume
 * @property string $source_type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MarketData extends Model
{
    protected $table = 'market_data';

    protected $fillable = [
        'data_connection_id',
        'symbol',
        'timeframe',
        'timestamp',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'source_type',
    ];

    protected $casts = [
        'timestamp' => 'integer',
        'open' => 'decimal:8',
        'high' => 'decimal:8',
        'low' => 'decimal:8',
        'close' => 'decimal:8',
        'volume' => 'decimal:8',
    ];

    /**
     * Relationships
     */
    
    public function dataConnection()
    {
        return $this->belongsTo(DataConnection::class);
    }

    /**
     * Scopes
     */
    
    public function scopeBySymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    public function scopeByTimeframe($query, string $timeframe)
    {
        return $query->where('timeframe', $timeframe);
    }

    public function scopeBetweenDates($query, int $startTimestamp, int $endTimestamp)
    {
        return $query->whereBetween('timestamp', [$startTimestamp, $endTimestamp]);
    }

    public function scopeRecent($query, string $symbol, string $timeframe, int $limit = 100)
    {
        return $query->where('symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->orderBy('timestamp', 'desc')
            ->limit($limit);
    }

    public function scopeLatest($query, string $symbol, string $timeframe)
    {
        return $query->where('symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->orderBy('timestamp', 'desc')
            ->first();
    }

    public function scopeOldData($query, int $retentionDays)
    {
        $cutoffTimestamp = now()->subDays($retentionDays)->timestamp;
        return $query->where('timestamp', '<', $cutoffTimestamp);
    }

    /**
     * Helper Methods
     */
    
    public function getCandleArray(): array
    {
        return [
            'timestamp' => $this->timestamp,
            'open' => (float) $this->open,
            'high' => (float) $this->high,
            'low' => (float) $this->low,
            'close' => (float) $this->close,
            'volume' => $this->volume ? (float) $this->volume : null,
        ];
    }

    public function getDatetime(): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromTimestamp($this->timestamp);
    }

    /**
     * Bulk insert with duplicate handling
     */
    public static function bulkInsert(array $candles): int
    {
        if (empty($candles)) {
            return 0;
        }

        try {
            // Use insertOrIgnore to skip duplicates (unique constraint)
            return self::insertOrIgnore($candles);
        } catch (\Exception $e) {
            \Log::error('Failed to bulk insert market data', [
                'error' => $e->getMessage(),
                'count' => count($candles),
            ]);
            return 0;
        }
    }
}

