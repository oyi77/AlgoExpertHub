<?php

namespace Addons\TradingManagement\Modules\DataProvider\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * DataConnectionLog Model
 * 
 * Logs all actions performed on data connections
 * 
 * @property int $id
 * @property int $data_connection_id
 * @property string $action (connect, disconnect, fetch_data, test, error)
 * @property string $status (success, failed)
 * @property string|null $message
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 */
class DataConnectionLog extends Model
{
    protected $table = 'data_connection_logs';

    public $timestamps = false; // Only created_at

    protected $fillable = [
        'data_connection_id',
        'action',
        'status',
        'message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
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
    
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeErrors($query)
    {
        return $query->where('action', 'error')
            ->orWhere('status', 'failed');
    }
}

