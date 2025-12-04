<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create backtests table
 * 
 * For storing backtest configurations and runs
 */
class CreateBacktestsTable extends Migration
{
    public function up()
    {
        Schema::create('backtests', function (Blueprint $table) {
            $table->id();
            
            // Ownership
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            
            // Configuration
            $table->string('name');
            $table->text('description')->nullable();
            
            // Strategy Components
            $table->unsignedBigInteger('filter_strategy_id')->nullable();
            $table->unsignedBigInteger('ai_model_profile_id')->nullable();
            $table->unsignedBigInteger('preset_id')->comment('Trading preset for position sizing');
            
            // Backtest Parameters
            $table->string('symbol');
            $table->string('timeframe');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('initial_balance', 20, 8)->default(10000);
            
            // Execution
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->integer('progress_percent')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('filter_strategy_id')->references('id')->on('filter_strategies')->onDelete('set null');
            $table->foreign('ai_model_profile_id')->references('id')->on('ai_model_profiles')->onDelete('set null');
            $table->foreign('preset_id')->references('id')->on('trading_presets')->onDelete('cascade');

            // Indexes
            $table->index('user_id');
            $table->index('admin_id');
            $table->index('status');
            $table->index('symbol');
        });
    }

    public function down()
    {
        Schema::dropIfExists('backtests');
    }
}

