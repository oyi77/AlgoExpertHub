<?php

namespace Addons\SmartRiskManagement\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbTestAssignment extends Model
{
    use HasFactory;

    protected $table = 'srm_ab_test_assignments';

    protected $fillable = [
        'ab_test_id',
        'user_id',
        'connection_id',
        'group_type',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    /**
     * Get the A/B test this assignment belongs to
     */
    public function abTest()
    {
        return $this->belongsTo(AbTest::class, 'ab_test_id');
    }

    /**
     * Get the user assigned to this test
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the connection assigned to this test
     */
    public function connection()
    {
        if (class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
            return $this->belongsTo(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class, 'connection_id');
        }
        return null;
    }

    /**
     * Scope for pilot group
     */
    public function scopePilot($query)
    {
        return $query->where('group_type', 'pilot');
    }

    /**
     * Scope for control group
     */
    public function scopeControl($query)
    {
        return $query->where('group_type', 'control');
    }
}

