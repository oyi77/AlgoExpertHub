<?php

namespace Addons\TradingExecutionEngine\App\Models;

use App\Models\Admin;
use App\Models\Signal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExecutionNotification extends Model
{
    use HasFactory;

    protected $table = 'execution_notifications';

    protected $fillable = [
        'user_id',
        'admin_id',
        'connection_id',
        'signal_id',
        'position_id',
        'type',
        'title',
        'message',
        'is_read',
        'metadata',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function connection()
    {
        return $this->belongsTo(ExecutionConnection::class);
    }

    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }

    public function position()
    {
        return $this->belongsTo(ExecutionPosition::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }
}

