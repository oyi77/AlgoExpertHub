<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dex_time_based_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('watchlist_id')->index();
            $table->string('wallet_address', 255)->index();
            $table->string('platform', 50)->index();
            $table->enum('time_period', ['1d', '7d', '30d', '90d', '180d', '365d', 'all_time'])->index();

            // Core metrics
            $table->decimal('total_pnl', 24, 8)->default(0);
            $table->decimal('win_rate', 8, 4)->default(0);
            $table->decimal('profit_factor', 12, 8)->default(0);
            $table->decimal('total_trades', 12, 0)->default(0);

            // Advanced metrics
            $table->decimal('sharpe_ratio', 12, 4)->default(0);
            $table->decimal('calmar_ratio', 12, 4)->default(0);
            $table->decimal('sortino_ratio', 12, 4)->default(0);
            $table->decimal('max_drawdown', 24, 8)->default(0);

            // Trade analysis
            $table->decimal('avg_trade_size', 24, 8)->default(0);
            $table->decimal('avg_winning_trade', 24, 8)->default(0);
            $table->decimal('avg_losing_trade', 24, 8)->default(0);
            $table->decimal('win_loss_ratio', 12, 4)->default(0);
            $table->decimal('avg_holding_time', 12, 2)->default(0);

            // Risk metrics
            $table->decimal('liquidation_rate', 8, 4)->default(0);
            $table->decimal('funding_cost_ratio', 12, 8)->default(0);
            $table->decimal('total_exposure', 24, 8)->default(0);

            // Classifications
            $table->string('pnl_category', 50)->default('break_even');
            $table->string('wallet_tier', 50)->default('shrimp');
            $table->decimal('consistency_score', 8, 4)->default(0);

            // Copy suitability
            $table->decimal('copy_suitability_score', 8, 4)->default(0);
            $table->string('copy_rating', 10)->default('F');

            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate entries
            $table->unique(['watchlist_id', 'time_period'], 'dex_metrics_unique');

            // Foreign key (optional - can be enabled if needed)
            // $table->foreign('watchlist_id')->references('id')->on('dex_trader_watchlist')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dex_time_based_metrics');
    }
};
