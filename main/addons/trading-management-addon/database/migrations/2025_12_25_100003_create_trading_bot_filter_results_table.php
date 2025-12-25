<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create trading_bot_filter_results table
 * 
 * Tracks filter evaluation results for bot signals
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('trading_bot_filter_results', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('bot_id')->comment('FK to trading_bots');
            $table->unsignedBigInteger('signal_id')->nullable()->comment('FK to signals');
            $table->unsignedBigInteger('filter_strategy_id')->comment('FK to filter_strategies');
            
            // Result
            $table->boolean('passed')->default(false)->comment('Whether filter passed');
            $table->json('result_data')->nullable()->comment('Filter result details (reason, indicators, priority)');
            
            // Timestamp
            $table->timestamp('executed_at')->comment('When filter was evaluated');
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('bot_id')->references('id')->on('trading_bots')->onDelete('cascade');
            if (Schema::hasTable('signals')) {
                $table->foreign('signal_id')->references('id')->on('signals')->onDelete('cascade');
            }
            if (Schema::hasTable('filter_strategies')) {
                $table->foreign('filter_strategy_id')->references('id')->on('filter_strategies')->onDelete('cascade');
            }
            
            // Indexes
            $table->index('bot_id', 'idx_bot_id');
            $table->index('signal_id', 'idx_signal_id');
            $table->index('filter_strategy_id', 'idx_filter_strategy_id');
            $table->index('executed_at', 'idx_executed_at');
            $table->index('passed', 'idx_passed');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trading_bot_filter_results');
    }
};

