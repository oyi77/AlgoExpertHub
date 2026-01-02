<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trading_bots', function (Blueprint $table) {
            // Add worker_status column to track queue job status
            $table->string('worker_status')->nullable()->after('worker_pid')
                ->comment('Queue worker status: queued, running, stopped, failed');
            
            // Add heartbeat timestamp to monitor worker health
            $table->timestamp('worker_last_heartbeat')->nullable()->after('worker_status')
                ->comment('Last heartbeat from worker to detect dead workers');
            
            // Note: Keeping worker_pid for backward compatibility during migration
            // Will be deprecated and removed in future version
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trading_bots', function (Blueprint $table) {
            $table->dropColumn(['worker_status', 'worker_last_heartbeat']);
        });
    }
};
