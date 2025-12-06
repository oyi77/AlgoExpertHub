<?php

namespace Addons\SmartRiskManagement\App\Models;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbTest extends Model
{
    use HasFactory;

    protected $table = 'srm_ab_tests';

    protected $fillable = [
        'name',
        'description',
        'status',
        'pilot_group_percentage',
        'test_duration_days',
        'control_logic',
        'pilot_logic',
        'start_date',
        'end_date',
        'pilot_group_size',
        'control_group_size',
        'pilot_avg_pnl',
        'control_avg_pnl',
        'pilot_avg_drawdown',
        'control_avg_drawdown',
        'pilot_win_rate',
        'control_win_rate',
        'p_value',
        'is_significant',
        'decision',
        'decision_notes',
        'created_by_admin_id',
    ];

    protected $casts = [
        'control_logic' => 'array',
        'pilot_logic' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'pilot_group_percentage' => 'decimal:2',
        'pilot_avg_pnl' => 'decimal:2',
        'control_avg_pnl' => 'decimal:2',
        'pilot_avg_drawdown' => 'decimal:2',
        'control_avg_drawdown' => 'decimal:2',
        'pilot_win_rate' => 'decimal:2',
        'control_win_rate' => 'decimal:2',
        'p_value' => 'decimal:6',
        'is_significant' => 'boolean',
    ];

    /**
     * Get the admin who created this test
     */
    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    /**
     * Get all assignments for this test
     */
    public function assignments()
    {
        return $this->hasMany(AbTestAssignment::class, 'ab_test_id');
    }

    /**
     * Get pilot group assignments
     */
    public function pilotAssignments()
    {
        return $this->hasMany(AbTestAssignment::class, 'ab_test_id')
            ->where('group_type', 'pilot');
    }

    /**
     * Get control group assignments
     */
    public function controlAssignments()
    {
        return $this->hasMany(AbTestAssignment::class, 'ab_test_id')
            ->where('group_type', 'control');
    }

    /**
     * Scope for running tests
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope for completed tests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Check if test is currently running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running' 
            && $this->start_date 
            && (!$this->end_date || $this->end_date->isFuture());
    }
}

