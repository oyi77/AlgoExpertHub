<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBacktestResultsTable extends Migration
{
    public function up()
    {
        Schema::create('backtest_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('backtest_id');
            
            // Summary Metrics
            $table->integer('total_trades')->default(0);
            $table->integer('winning_trades')->default(0);
            $table->integer('losing_trades')->default(0);
            $table->decimal('win_rate', 5, 2)->default(0);
            
            // Financial Metrics
            $table->decimal('total_profit', 20, 8)->default(0);
            $table->decimal('total_loss', 20, 8)->default(0);
            $table->decimal('net_profit', 20, 8)->default(0);
            $table->decimal('final_balance', 20, 8)->default(0);
            $table->decimal('return_percent', 10, 4)->default(0);
            
            // Risk Metrics
            $table->decimal('profit_factor', 10, 4)->default(0);
            $table->decimal('sharpe_ratio', 10, 4)->nullable();
            $table->decimal('max_drawdown', 20, 8)->default(0);
            $table->decimal('max_drawdown_percent', 10, 4)->default(0);
            
            // Trade Metrics
            $table->decimal('avg_win', 20, 8)->default(0);
            $table->decimal('avg_loss', 20, 8)->default(0);
            $table->decimal('largest_win', 20, 8)->default(0);
            $table->decimal('largest_loss', 20, 8)->default(0);
            $table->integer('consecutive_wins')->default(0);
            $table->integer('consecutive_losses')->default(0);
            
            // Execution Details
            $table->json('equity_curve')->nullable()->comment('Array of balance over time');
            $table->json('trade_details')->nullable()->comment('Individual trade results');
            
            $table->timestamps();

            $table->foreign('backtest_id')->references('id')->on('backtests')->onDelete('cascade');
            $table->index('backtest_id');
            $table->unique('backtest_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('backtest_results');
    }
}

