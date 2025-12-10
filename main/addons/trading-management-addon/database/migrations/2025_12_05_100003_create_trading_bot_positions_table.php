<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create trading_bot_positions table
 * 
 * Tracks positions opened by trading bots (links to execution_positions for actual exchange positions)
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('trading_bot_positions', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('bot_id')->comment('FK to trading_bots');
            $table->unsignedBigInteger('signal_id')->nullable()->comment('FK to signals (if SIGNAL_BASED)');
            $table->unsignedBigInteger('execution_position_id')->nullable()->comment('FK to execution_positions (actual exchange position)');
            
            // Position details
            $table->string('symbol', 50)->comment('Trading pair (BTC/USDT, EURUSD, etc.)');
            $table->enum('direction', ['buy', 'sell', 'long', 'short'])->comment('Trade direction');
            $table->decimal('entry_price', 20, 8)->comment('Entry price');
            $table->decimal('current_price', 20, 8)->nullable()->comment('Current market price');
            $table->decimal('stop_loss', 20, 8)->nullable()->comment('Stop loss price');
            $table->decimal('take_profit', 20, 8)->nullable()->comment('Take profit price');
            $table->decimal('quantity', 20, 8)->comment('Position size/quantity');
            
            // Status and P/L
            $table->enum('status', ['open', 'closed', 'cancelled'])->default('open')->comment('Position status');
            $table->decimal('profit_loss', 15, 2)->default(0)->comment('Realized P/L (0 if open)');
            $table->string('close_reason')->nullable()->comment('Reason for closing (sl_hit, tp_hit, manual, etc.)');
            
            // Timestamps
            $table->timestamp('opened_at')->comment('When position was opened');
            $table->timestamp('closed_at')->nullable()->comment('When position was closed');
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('bot_id')->references('id')->on('trading_bots')->onDelete('cascade');
            if (Schema::hasTable('signals')) {
                $table->foreign('signal_id')->references('id')->on('signals')->onDelete('set null');
            }
            if (Schema::hasTable('execution_positions')) {
                $table->foreign('execution_position_id')->references('id')->on('execution_positions')->onDelete('set null');
            }
            
            // Indexes
            $table->index('bot_id');
            $table->index('status');
            $table->index('symbol');
            $table->index('opened_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trading_bot_positions');
    }
};

