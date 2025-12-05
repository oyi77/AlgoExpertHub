<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add trading_mode field to trading_bots table
 * 
 * Trading mode: SIGNAL_BASED (only execute on signals) or MARKET_STREAM_BASED (stream OHLCV data and apply technical indicators)
 */
class AddTradingModeToTradingBotsTable extends Migration
{
    public function up()
    {
        Schema::table('trading_bots', function (Blueprint $table) {
            $table->enum('trading_mode', ['SIGNAL_BASED', 'MARKET_STREAM_BASED'])
                  ->default('SIGNAL_BASED')
                  ->after('is_paper_trading')
                  ->comment('SIGNAL_BASED: Execute only on signals | MARKET_STREAM_BASED: Stream OHLCV data and apply technical indicators');
        });
    }

    public function down()
    {
        Schema::table('trading_bots', function (Blueprint $table) {
            $table->dropColumn('trading_mode');
        });
    }
}
