<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create market_data table
 * 
 * For storing OHLCV (candlestick) data from all providers
 * Centralized storage with caching support
 */
class CreateMarketDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('market_data', function (Blueprint $table) {
            $table->id();
            
            // Source
            $table->unsignedBigInteger('data_connection_id')->comment('Source connection');
            
            // Symbol and timeframe
            $table->string('symbol', 50)->comment('Trading pair (EURUSD, BTC/USDT, etc.)');
            $table->enum('timeframe', ['M1', 'M5', 'M15', 'M30', 'H1', 'H4', 'D1', 'W1', 'MN'])
                ->comment('Timeframe');
            
            // Timestamp (candle open time)
            $table->unsignedBigInteger('timestamp')->comment('Unix timestamp (candle open time)');
            
            // OHLCV data
            $table->decimal('open', 20, 8);
            $table->decimal('high', 20, 8);
            $table->decimal('low', 20, 8);
            $table->decimal('close', 20, 8);
            $table->decimal('volume', 20, 8)->nullable()->comment('Volume (null for FX pairs)');
            
            // Metadata
            $table->string('source_type', 50)->default('unknown')->comment('mtapi, ccxt, etc.');
            
            $table->timestamps();

            // Foreign key
            $table->foreign('data_connection_id')->references('id')->on('data_connections')
                ->onDelete('cascade');

            // Unique constraint: Prevent duplicate candles
            $table->unique(['data_connection_id', 'symbol', 'timeframe', 'timestamp'], 
                'unique_candle');

            // Indexes for fast queries
            $table->index(['symbol', 'timeframe', 'timestamp'], 'symbol_time_idx');
            $table->index('data_connection_id');
            $table->index('created_at'); // For cleanup queries
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('market_data');
    }
}

