<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create trading_bot_analytics table
 * 
 * Stores daily analytics aggregation for trading bots
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('trading_bot_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bot_id')->comment('FK to trading_bots');
            $table->date('date')->comment('Analytics date');
            
            // Trade statistics
            $table->integer('total_trades')->default(0)->comment('Total trades executed');
            $table->integer('winning_trades')->default(0)->comment('Winning trades');
            $table->integer('losing_trades')->default(0)->comment('Losing trades');
            
            // Profit/Loss metrics
            $table->decimal('total_profit', 15, 8)->default(0)->comment('Total profit');
            $table->decimal('total_loss', 15, 8)->default(0)->comment('Total loss');
            
            // Performance metrics
            $table->decimal('max_drawdown', 15, 8)->default(0)->comment('Maximum drawdown');
            $table->decimal('sharpe_ratio', 10, 4)->nullable()->comment('Sharpe ratio');
            $table->decimal('profit_factor', 10, 4)->nullable()->comment('Profit factor');
            
            $table->timestamps();
            
            // Foreign key
            $table->foreign('bot_id')->references('id')->on('trading_bots')->onDelete('cascade');
            
            // Indexes
            $table->unique(['bot_id', 'date'], 'unique_bot_date');
            $table->index('bot_id', 'idx_bot_id');
            $table->index('date', 'idx_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trading_bot_analytics');
    }
};

