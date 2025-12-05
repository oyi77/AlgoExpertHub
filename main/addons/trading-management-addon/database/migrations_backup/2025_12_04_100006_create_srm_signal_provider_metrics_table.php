<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create srm_signal_provider_metrics table
 * 
 * Migrated from smart-risk-management-addon
 * For tracking signal provider performance (for AI adaptive risk)
 */
class CreateSrmSignalProviderMetricsTable extends Migration
{
    public function up()
    {
        // Skip if table already exists
        if (Schema::hasTable('srm_signal_provider_metrics')) {
            return;
        }

        Schema::create('srm_signal_provider_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('provider_id')->comment('Signal source identifier');
            $table->string('symbol', 50);
            $table->string('timeframe', 10);
            
            // Performance Metrics
            $table->integer('total_signals')->default(0);
            $table->integer('winning_signals')->default(0);
            $table->integer('losing_signals')->default(0);
            $table->decimal('win_rate', 5, 2)->default(0);
            $table->decimal('avg_profit', 15, 2)->default(0);
            $table->decimal('avg_loss', 15, 2)->default(0);
            $table->decimal('profit_factor', 10, 4)->default(0);
            $table->decimal('sharpe_ratio', 10, 4)->nullable();
            
            // Performance Score (0-100)
            $table->decimal('performance_score', 5, 2)->default(50)->comment('Weighted performance score');
            
            // Volatility
            $table->decimal('avg_slippage_pips', 10, 2)->default(0);
            $table->decimal('max_slippage_pips', 10, 2)->default(0);
            
            // Last Update
            $table->timestamp('last_calculated_at')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index(['provider_id', 'symbol', 'timeframe'], 'provider_symbol_tf_idx');
            $table->index('performance_score');
        });
    }

    public function down()
    {
        Schema::dropIfExists('srm_signal_provider_metrics');
    }
}

