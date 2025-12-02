<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTradingPresetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trading_presets', function (Blueprint $table) {
            $table->id();
            
            // Identity & Market
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('symbol', 50)->nullable()->comment('Logical symbol (e.g., XAUUSD)');
            $table->string('timeframe', 10)->nullable()->comment('M1, M5, M15, H1, etc.');
            $table->boolean('enabled')->default(true);
            $table->json('tags')->nullable()->comment('Array of tags: ["scalping", "xau", "layering"]');
            
            // Position & Risk
            $table->enum('position_size_mode', ['FIXED', 'RISK_PERCENT'])->default('RISK_PERCENT');
            $table->decimal('fixed_lot', 10, 2)->nullable();
            $table->decimal('risk_per_trade_pct', 5, 2)->nullable()->comment('Percentage of equity');
            $table->unsignedInteger('max_positions')->default(1);
            $table->unsignedInteger('max_positions_per_symbol')->default(1);
            
            // Dynamic Equity
            $table->enum('equity_dynamic_mode', ['NONE', 'LINEAR', 'STEP'])->default('NONE');
            $table->decimal('equity_base', 15, 2)->nullable()->comment('Base equity amount');
            $table->decimal('equity_step_factor', 5, 2)->nullable()->comment('Multiplier for step mode');
            $table->decimal('risk_min_pct', 5, 2)->nullable();
            $table->decimal('risk_max_pct', 5, 2)->nullable();
            
            // Stop Loss
            $table->enum('sl_mode', ['PIPS', 'R_MULTIPLE', 'STRUCTURE'])->default('PIPS');
            $table->integer('sl_pips')->nullable();
            $table->decimal('sl_r_multiple', 5, 2)->nullable()->comment('R multiple (e.g., 1.5R)');
            
            // Take Profit
            $table->enum('tp_mode', ['DISABLED', 'SINGLE', 'MULTI'])->default('SINGLE');
            
            // TP1
            $table->boolean('tp1_enabled')->default(true);
            $table->decimal('tp1_rr', 5, 2)->nullable()->comment('Risk:Reward ratio');
            $table->decimal('tp1_close_pct', 5, 2)->nullable()->comment('Percentage to close at TP1');
            
            // TP2
            $table->boolean('tp2_enabled')->default(false);
            $table->decimal('tp2_rr', 5, 2)->nullable();
            $table->decimal('tp2_close_pct', 5, 2)->nullable();
            
            // TP3
            $table->boolean('tp3_enabled')->default(false);
            $table->decimal('tp3_rr', 5, 2)->nullable();
            $table->decimal('tp3_close_pct', 5, 2)->nullable();
            $table->boolean('close_remaining_at_tp3')->default(false);
            
            // Break Even
            $table->boolean('be_enabled')->default(false);
            $table->decimal('be_trigger_rr', 5, 2)->nullable()->comment('Trigger BE when this RR is reached');
            $table->integer('be_offset_pips')->nullable()->comment('Offset from entry (can be negative)');
            
            // Trailing Stop
            $table->boolean('ts_enabled')->default(false);
            $table->enum('ts_mode', ['STEP_PIPS', 'STEP_ATR', 'CHANDELIER'])->default('STEP_PIPS');
            $table->decimal('ts_trigger_rr', 5, 2)->nullable()->comment('Start trailing after this RR');
            $table->integer('ts_step_pips')->nullable();
            $table->integer('ts_atr_period')->nullable()->comment('For ATR mode');
            $table->decimal('ts_atr_multiplier', 5, 2)->nullable();
            $table->integer('ts_update_interval_sec')->nullable()->comment('Update frequency');
            
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
            $table->decimal('hedge_lot_factor', 5, 2)->nullable()->comment('Multiplier for hedge lot size');
            
            // Exit Per Candle
            $table->boolean('auto_close_on_candle_close')->default(false);
            $table->string('auto_close_timeframe', 10)->nullable()->comment('M5, M15, etc.');
            $table->integer('hold_max_candles')->nullable();
            
            // Trading Schedule
            $table->time('trading_hours_start')->nullable()->comment('HH:MM format');
            $table->time('trading_hours_end')->nullable();
            $table->string('trading_timezone', 50)->default('SERVER');
            $table->unsignedInteger('trading_days_mask')->default(127)->comment('Bitmask: 1=Mon, 2=Tue, 4=Wed, 8=Thu, 16=Fri, 32=Sat, 64=Sun');
            $table->enum('session_profile', ['ASIA', 'LONDON', 'NY', 'CUSTOM'])->default('CUSTOM');
            $table->boolean('only_trade_in_session')->default(false);
            
            // Weekly Target
            $table->boolean('weekly_target_enabled')->default(false);
            $table->decimal('weekly_target_profit_pct', 5, 2)->nullable();
            $table->unsignedTinyInteger('weekly_reset_day')->nullable()->comment('1=Monday, 7=Sunday');
            $table->boolean('auto_stop_on_weekly_target')->default(false);
            
            // Meta
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->boolean('is_default_template')->default(false);
            $table->boolean('clonable')->default(true);
            $table->enum('visibility', ['PRIVATE', 'PUBLIC_MARKETPLACE'])->default('PRIVATE');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('created_by_user_id');
            $table->index('visibility');
            $table->index('enabled');
            $table->index('is_default_template');
            $table->index('symbol');
            $table->index('timeframe');
            
            // Foreign Keys
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trading_presets');
    }
}

