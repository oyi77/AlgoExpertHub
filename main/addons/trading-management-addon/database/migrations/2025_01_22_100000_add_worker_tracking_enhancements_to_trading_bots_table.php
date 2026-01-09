<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add worker tracking enhancements to trading_bots table
 * 
 * Adds fields for enhanced worker process management:
 * - worker_restart_count: Track number of restarts
 * - worker_last_restart_at: Timestamp of last restart
 * - health_status: Worker health status (healthy, unhealthy, unknown)
 * - health_checked_at: Last health check timestamp
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trading_bots', function (Blueprint $table) {
            // First add worker_last_heartbeat if it doesn't exist
            if (!Schema::hasColumn('trading_bots', 'worker_last_heartbeat')) {
                $table->timestamp('worker_last_heartbeat')
                    ->nullable()
                    ->after('worker_started_at')
                    ->comment('Last heartbeat from worker to detect dead workers');
            }

            // Worker restart tracking
            if (!Schema::hasColumn('trading_bots', 'worker_restart_count')) {
                $table->unsignedInteger('worker_restart_count')
                    ->default(0)
                    ->after('worker_last_heartbeat')
                    ->comment('Number of times worker has been restarted');
            }

            if (!Schema::hasColumn('trading_bots', 'worker_last_restart_at')) {
                $table->timestamp('worker_last_restart_at')
                    ->nullable()
                    ->after('worker_restart_count')
                    ->comment('Timestamp of last worker restart');
            }

            // Health status tracking
            if (!Schema::hasColumn('trading_bots', 'health_status')) {
                $table->enum('health_status', ['healthy', 'unhealthy', 'unknown'])
                    ->default('unknown')
                    ->after('worker_last_restart_at')
                    ->comment('Worker health status');
            }

            if (!Schema::hasColumn('trading_bots', 'health_checked_at')) {
                $table->timestamp('health_checked_at')
                    ->nullable()
                    ->after('health_status')
                    ->comment('Last health check timestamp');
            }

            // Worker status for queue-based workers
            if (!Schema::hasColumn('trading_bots', 'worker_status')) {
                $table->string('worker_status')
                    ->nullable()
                    ->after('worker_pid')
                    ->comment('Queue worker status: queued, running, stopped, failed');
            }

            // Indexes for performance
            $table->index('worker_restart_count');
            $table->index('health_status');
            $table->index('worker_status');
        });
    }

    public function down(): void
    {
        Schema::table('trading_bots', function (Blueprint $table) {
            // Drop indexes
            if (Schema::hasColumn('trading_bots', 'worker_restart_count')) {
                $table->dropIndex(['worker_restart_count']);
            }
            if (Schema::hasColumn('trading_bots', 'health_status')) {
                $table->dropIndex(['health_status']);
            }
            if (Schema::hasColumn('trading_bots', 'worker_status')) {
                $table->dropIndex(['worker_status']);
            }
            
            // Drop columns in reverse order
            if (Schema::hasColumn('trading_bots', 'health_checked_at')) {
                $table->dropColumn('health_checked_at');
            }
            if (Schema::hasColumn('trading_bots', 'health_status')) {
                $table->dropColumn('health_status');
            }
            if (Schema::hasColumn('trading_bots', 'worker_last_restart_at')) {
                $table->dropColumn('worker_last_restart_at');
            }
            if (Schema::hasColumn('trading_bots', 'worker_restart_count')) {
                $table->dropColumn('worker_restart_count');
            }
            if (Schema::hasColumn('trading_bots', 'worker_last_heartbeat')) {
                $table->dropColumn('worker_last_heartbeat');
            }
            if (Schema::hasColumn('trading_bots', 'worker_status')) {
                $table->dropColumn('worker_status');
            }
        });
    }
};

