<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add bot lifecycle fields to trading_bots table
 * 
 * Adds status management, data connection, worker tracking, and monitoring intervals
 */
class AddBotLifecycleFieldsToTradingBotsTable extends Migration
{
    public function up()
    {
        Schema::table('trading_bots', function (Blueprint $table) {
            // Status management
            $table->enum('status', ['stopped', 'running', 'paused'])
                  ->default('stopped')
                  ->after('trading_mode')
                  ->comment('Bot lifecycle status');
            
            // Data connection for MARKET_STREAM_BASED mode
            $table->unsignedBigInteger('data_connection_id')
                  ->nullable()
                  ->after('exchange_connection_id')
                  ->comment('FK to exchange_connections for OHLCV streaming (MARKET_STREAM_BASED only)');
            
            // Worker process tracking
            $table->unsignedInteger('worker_pid')
                  ->nullable()
                  ->after('status')
                  ->comment('Process ID of background worker');
            
            // Timestamps
            $table->timestamp('last_started_at')->nullable()->after('worker_pid');
            $table->timestamp('last_stopped_at')->nullable()->after('last_started_at');
            $table->timestamp('last_paused_at')->nullable()->after('last_stopped_at');
            $table->timestamp('worker_started_at')->nullable()->after('last_paused_at');
            $table->timestamp('last_market_analysis_at')->nullable()->after('worker_started_at');
            $table->timestamp('last_position_check_at')->nullable()->after('last_market_analysis_at');
            
            // Streaming configuration (for MARKET_STREAM_BASED)
            $table->json('streaming_symbols')
                  ->nullable()
                  ->after('last_position_check_at')
                  ->comment('Symbols to stream (e.g., ["BTC/USDT", "ETH/USDT"])');
            
            $table->json('streaming_timeframes')
                  ->nullable()
                  ->after('streaming_symbols')
                  ->comment('Timeframes to stream (e.g., ["1h", "4h", "1d"])');
            
            // Monitoring intervals (in seconds)
            $table->unsignedInteger('position_monitoring_interval')
                  ->default(5)
                  ->after('streaming_timeframes')
                  ->comment('How often to check SL/TP (seconds)');
            
            $table->unsignedInteger('market_analysis_interval')
                  ->default(60)
                  ->after('position_monitoring_interval')
                  ->comment('How often to analyze market for MARKET_STREAM_BASED (seconds)');
            
            // Foreign key for data connection
            if (Schema::hasTable('exchange_connections')) {
                $table->foreign('data_connection_id')
                      ->references('id')
                      ->on('exchange_connections')
                      ->onDelete('set null');
            }
            
            // Indexes
            $table->index('status');
            $table->index('data_connection_id');
            $table->index('worker_pid');
        });
    }

    public function down()
    {
        Schema::table('trading_bots', function (Blueprint $table) {
            $table->dropForeign(['data_connection_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['data_connection_id']);
            $table->dropIndex(['worker_pid']);
            
            $table->dropColumn([
                'status',
                'data_connection_id',
                'worker_pid',
                'last_started_at',
                'last_stopped_at',
                'last_paused_at',
                'worker_started_at',
                'last_market_analysis_at',
                'last_position_check_at',
                'streaming_symbols',
                'streaming_timeframes',
                'position_monitoring_interval',
                'market_analysis_interval',
            ]);
        });
    }
}
