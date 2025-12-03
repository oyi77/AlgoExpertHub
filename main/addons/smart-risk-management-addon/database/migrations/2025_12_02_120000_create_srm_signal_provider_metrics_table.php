<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('srm_signal_provider_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('signal_provider_id', 255)->comment('channel_source_id or user_id');
            $table->enum('signal_provider_type', ['channel_source', 'user'])->comment('Type of signal provider');
            $table->date('period_start')->comment('Period start date');
            $table->date('period_end')->comment('Period end date');
            $table->enum('period_type', ['daily', 'weekly', 'monthly'])->default('daily')->comment('Period type');
            
            // Metrics
            $table->unsignedInteger('total_signals')->default(0)->comment('Total signals in period');
            $table->unsignedInteger('winning_signals')->default(0)->comment('Winning signals count');
            $table->unsignedInteger('losing_signals')->default(0)->comment('Losing signals count');
            $table->decimal('win_rate', 5, 2)->default(0.00)->comment('Win rate percentage');
            $table->decimal('avg_slippage', 8, 4)->default(0.0000)->comment('Average slippage in pips');
            $table->decimal('max_slippage', 8, 4)->default(0.0000)->comment('Maximum slippage in pips');
            $table->unsignedInteger('avg_latency_ms')->default(0)->comment('Average latency in milliseconds');
            $table->decimal('max_drawdown', 5, 2)->default(0.00)->comment('Maximum drawdown percentage');
            $table->decimal('reward_to_risk_ratio', 8, 4)->default(0.0000)->comment('Reward to risk ratio');
            $table->decimal('sl_compliance_rate', 5, 2)->default(0.00)->comment('SL compliance rate percentage');
            
            // Performance Score
            $table->decimal('performance_score', 5, 2)->default(50.00)->comment('Performance score 0-100');
            $table->decimal('performance_score_previous', 5, 2)->default(50.00)->comment('Previous performance score');
            $table->enum('score_trend', ['up', 'down', 'stable'])->default('stable')->comment('Score trend');
            
            // Metadata
            $table->timestamp('calculated_at')->nullable()->comment('When metrics were calculated');
            $table->timestamps();
            
            // Indexes
            $table->index(['signal_provider_id', 'signal_provider_type'], 'idx_signal_provider');
            $table->index(['period_start', 'period_end', 'period_type'], 'idx_period');
            $table->index('performance_score', 'idx_performance_score');
            $table->unique(['signal_provider_id', 'signal_provider_type', 'period_start', 'period_end', 'period_type'], 'uk_provider_period');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('srm_signal_provider_metrics');
    }
};

