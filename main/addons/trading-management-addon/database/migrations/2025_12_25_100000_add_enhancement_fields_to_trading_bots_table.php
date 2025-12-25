<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add enhancement fields to trading_bots table
 * 
 * Adds filter_priority, data_fetch_interval, health_status, and health_checked_at
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('trading_bots', function (Blueprint $table) {
            // Filter priority configuration (JSON array of filter configs)
            if (!Schema::hasColumn('trading_bots', 'filter_priority')) {
                $table->json('filter_priority')->nullable()->after('filter_strategy_id')
                    ->comment('Array of filter configurations with priority order');
            }

            // Data fetch interval in seconds
            if (!Schema::hasColumn('trading_bots', 'data_fetch_interval')) {
                $table->integer('data_fetch_interval')->default(60)->after('market_analysis_interval')
                    ->comment('Interval in seconds for fetching market data');
            }

            // Health status
            if (!Schema::hasColumn('trading_bots', 'health_status')) {
                $table->enum('health_status', ['healthy', 'warning', 'error'])->default('healthy')
                    ->after('status')
                    ->comment('Bot health status');
            }

            // Last health check timestamp
            if (!Schema::hasColumn('trading_bots', 'health_checked_at')) {
                $table->timestamp('health_checked_at')->nullable()->after('health_status')
                    ->comment('Last time health check was performed');
            }

            // Index for health status queries
            if (!Schema::hasIndex('trading_bots', 'idx_health_status')) {
                $table->index('health_status', 'idx_health_status');
            }
        });
    }

    public function down()
    {
        Schema::table('trading_bots', function (Blueprint $table) {
            if (Schema::hasIndex('trading_bots', 'idx_health_status')) {
                $table->dropIndex('idx_health_status');
            }

            if (Schema::hasColumn('trading_bots', 'health_checked_at')) {
                $table->dropColumn('health_checked_at');
            }

            if (Schema::hasColumn('trading_bots', 'health_status')) {
                $table->dropColumn('health_status');
            }

            if (Schema::hasColumn('trading_bots', 'data_fetch_interval')) {
                $table->dropColumn('data_fetch_interval');
            }

            if (Schema::hasColumn('trading_bots', 'filter_priority')) {
                $table->dropColumn('filter_priority');
            }
        });
    }
};

