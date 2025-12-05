<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create trading_presets table
 * 
 * Migrated from trading-preset-addon to trading-management-addon
 * Comprehensive risk management presets with:
 * - Manual presets (position sizing, SL/TP, multi-TP)
 * - Smart Risk integration (AI adaptive risk)
 * - Filter strategy integration
 * - AI model profile integration
 */
class CreateTradingPresetsTableInTradingManagement extends Migration
{
    public function up()
    {
        // Skip if table already exists (created by trading-preset-addon migration)
        if (Schema::hasTable('trading_presets')) {
            return;
        }

        Schema::create('trading_presets', function (Blueprint $table) {
            $table->id();
            
            // Identity & Market
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('symbol', 50)->nullable();
            $table->string('timeframe', 10)->nullable();
            $table->boolean('enabled')->default(true);
            $table->json('tags')->nullable();
            
            // Position & Risk
            $table->enum('position_size_mode', ['FIXED', 'RISK_PERCENT'])->default('RISK_PERCENT');
            $table->decimal('fixed_lot', 10, 2)->nullable();
            $table->decimal('risk_per_trade_pct', 5, 2)->nullable();
            $table->unsignedInteger('max_positions')->default(1);
            $table->unsignedInteger('max_positions_per_symbol')->default(1);
            
            // Dynamic Equity
            $table->enum('equity_dynamic_mode', ['NONE', 'LINEAR', 'STEP'])->default('NONE');
            $table->decimal('equity_base', 15, 2)->nullable();
            $table->decimal('equity_step_factor', 5, 2)->nullable();
            $table->decimal('risk_min_pct', 5, 2)->nullable();
            $table->decimal('risk_max_pct', 5, 2)->nullable();
            
            // Stop Loss
            $table->enum('sl_mode', ['PIPS', 'R_MULTIPLE', 'STRUCTURE'])->default('PIPS');
            $table->integer('sl_pips')->nullable();
            $table->decimal('sl_r_multiple', 5, 2)->nullable();
            
            // Take Profit
            $table->enum('tp_mode', ['DISABLED', 'SINGLE', 'MULTI'])->default('SINGLE');
            $table->boolean('tp1_enabled')->default(true);
            $table->decimal('tp1_rr', 5, 2)->nullable();
            $table->decimal('tp1_close_pct', 5, 2)->nullable();
            $table->boolean('tp2_enabled')->default(false);
            $table->decimal('tp2_rr', 5, 2)->nullable();
            $table->decimal('tp2_close_pct', 5, 2)->nullable();
            $table->boolean('tp3_enabled')->default(false);
            $table->decimal('tp3_rr', 5, 2)->nullable();
            $table->decimal('tp3_close_pct', 5, 2)->nullable();
            $table->boolean('close_remaining_at_tp3')->default(false);
            
            // Break Even
            $table->boolean('be_enabled')->default(false);
            $table->decimal('be_trigger_rr', 5, 2)->nullable();
            $table->integer('be_offset_pips')->nullable();
            
            // Trailing Stop
            $table->boolean('ts_enabled')->default(false);
            $table->enum('ts_mode', ['STEP_PIPS', 'STEP_ATR', 'CHANDELIER'])->default('STEP_PIPS');
            $table->decimal('ts_trigger_rr', 5, 2)->nullable();
            $table->integer('ts_step_pips')->nullable();
            $table->integer('ts_atr_period')->nullable();
            $table->decimal('ts_atr_multiplier', 5, 2)->nullable();
            $table->integer('ts_update_interval_sec')->nullable();
            
            // Layering / Grid
            $table->boolean('layering_enabled')->default(false);
            $table->unsignedInteger('max_layers_per_symbol')->default(3);
            $table->integer('layer_distance_pips')->nullable();
            $table->enum('layer_martingale_mode', ['NONE', 'MULTIPLY', 'ADD'])->default('NONE');
            $table->decimal('layer_martingale_factor', 5, 2)->nullable();
            $table->decimal('layer_max_total_risk_pct', 5, 2)->nullable();
            
            // Hedging
            $table->boolean('hedging_enabled')->default(false);
            $table->decimal('hedge_trigger_drawdown_pct', 5, 2)->nullable();
            $table->integer('hedge_distance_pips')->nullable();
            $table->decimal('hedge_lot_factor', 5, 2)->nullable();
            
            // Exit Per Candle
            $table->boolean('auto_close_on_candle_close')->default(false);
            $table->string('auto_close_timeframe', 10)->nullable();
            $table->integer('hold_max_candles')->nullable();
            
            // Trading Schedule
            $table->time('trading_hours_start')->nullable();
            $table->time('trading_hours_end')->nullable();
            $table->string('trading_timezone', 50)->default('UTC');
            $table->integer('trading_days_mask')->nullable();
            $table->enum('session_profile', ['NONE', 'ASIAN', 'LONDON', 'NY', 'SYDNEY'])->default('NONE');
            $table->boolean('only_trade_in_session')->default(false);
            
            // Weekly Target
            $table->boolean('weekly_target_enabled')->default(false);
            $table->decimal('weekly_target_profit_pct', 5, 2)->nullable();
            $table->enum('weekly_reset_day', ['MONDAY', 'SUNDAY'])->default('MONDAY');
            $table->boolean('auto_stop_on_weekly_target')->default(false);
            
            // Meta
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->boolean('is_default_template')->default(false);
            $table->boolean('clonable')->default(true);
            $table->enum('visibility', ['PRIVATE', 'PUBLIC_MARKETPLACE'])->default('PRIVATE');
            
            // Integration: Filter Strategy (Phase 3)
            $table->unsignedBigInteger('filter_strategy_id')->nullable();
            
            // Integration: AI Model Profile (Phase 3)
            $table->unsignedBigInteger('ai_model_profile_id')->nullable();
            $table->enum('ai_confirmation_mode', ['NONE', 'REQUIRED', 'ADVISORY'])->default('NONE');
            $table->decimal('ai_min_safety_score', 5, 2)->nullable();
            $table->boolean('ai_position_mgmt_enabled')->default(false);
            
            // Integration: Smart Risk (Phase 4 - NEW)
            $table->boolean('smart_risk_enabled')->default(false)->comment('Enable AI adaptive risk');
            $table->decimal('smart_risk_min_score', 5, 2)->nullable()->comment('Min provider score (0-100)');
            $table->boolean('smart_risk_slippage_buffer')->default(false)->comment('Auto-adjust SL for slippage');
            $table->boolean('smart_risk_dynamic_lot')->default(false)->comment('Adjust lot based on performance');
            
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('filter_strategy_id')->references('id')->on('filter_strategies')->onDelete('set null');
            $table->foreign('ai_model_profile_id')->references('id')->on('ai_model_profiles')->onDelete('set null');

            // Indexes
            $table->index('created_by_user_id');
            $table->index('filter_strategy_id');
            $table->index('ai_model_profile_id');
            $table->index('enabled');
            $table->index('visibility');
            $table->index('is_default_template');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trading_presets');
    }
}

