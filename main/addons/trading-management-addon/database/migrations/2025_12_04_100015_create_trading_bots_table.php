<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create trading_bots table
 * 
 * Trading Bot Builder - Coinrule-like bot creation
 * Combines: Exchange Connection + Trading Preset + Filter Strategy + AI Profile
 */
class CreateTradingBotsTable extends Migration
{
    public function up()
    {
        Schema::create('trading_bots', function (Blueprint $table) {
            $table->id();
            
            // Ownership
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            
            // Bot Identity
            $table->string('name');
            $table->text('description')->nullable();
            
            // Bot Configuration (combines all components)
            $table->unsignedBigInteger('exchange_connection_id')->comment('FK to exchange_connections');
            $table->unsignedBigInteger('trading_preset_id')->comment('FK to trading_presets');
            $table->unsignedBigInteger('filter_strategy_id')->nullable()->comment('FK to filter_strategies (optional)');
            $table->unsignedBigInteger('ai_model_profile_id')->nullable()->comment('FK to ai_model_profiles (optional)');
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_paper_trading')->default(true)->comment('Paper trading mode (demo)');
            
            // Statistics (cached for performance)
            $table->unsignedInteger('total_executions')->default(0);
            $table->unsignedInteger('successful_executions')->default(0);
            $table->unsignedInteger('failed_executions')->default(0);
            $table->decimal('total_profit', 15, 2)->default(0);
            $table->decimal('win_rate', 5, 2)->default(0)->comment('Percentage');
            
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            if (Schema::hasTable('exchange_connections')) {
                $table->foreign('exchange_connection_id')->references('id')->on('exchange_connections')->onDelete('cascade');
            } elseif (Schema::hasTable('execution_connections')) {
                // Fallback for backward compatibility
                $table->foreign('exchange_connection_id')->references('id')->on('execution_connections')->onDelete('cascade');
            }
            if (Schema::hasTable('trading_presets')) {
                $table->foreign('trading_preset_id')->references('id')->on('trading_presets')->onDelete('cascade');
            }
            if (Schema::hasTable('filter_strategies')) {
                $table->foreign('filter_strategy_id')->references('id')->on('filter_strategies')->onDelete('set null');
            }
            if (Schema::hasTable('ai_model_profiles')) {
                $table->foreign('ai_model_profile_id')->references('id')->on('ai_model_profiles')->onDelete('set null');
            }

            // Indexes
            $table->index('user_id');
            $table->index('admin_id');
            $table->index('is_active');
            $table->index('exchange_connection_id');
            $table->index('trading_preset_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trading_bots');
    }
}
