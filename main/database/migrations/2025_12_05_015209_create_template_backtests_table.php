<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplateBacktestsTable extends Migration
{
    public function up()
    {
        Schema::create('template_backtests', function (Blueprint $table) {
            $table->id();
            $table->enum('template_type', ['bot', 'signal', 'complete']);
            $table->unsignedBigInteger('template_id');
            $table->decimal('capital_initial', 15, 2);
            $table->decimal('capital_final', 15, 2);
            $table->decimal('net_profit_percent', 10, 2);
            $table->decimal('win_rate', 5, 2);
            $table->decimal('profit_factor', 10, 4)->nullable();
            $table->decimal('max_drawdown', 10, 2)->nullable();
            $table->integer('total_trades')->default(0);
            $table->integer('winning_trades')->default(0);
            $table->integer('losing_trades')->default(0);
            $table->decimal('avg_win_percent', 10, 2)->nullable();
            $table->decimal('avg_loss_percent', 10, 2)->nullable();
            $table->timestamp('backtest_period_start')->nullable();
            $table->timestamp('backtest_period_end')->nullable();
            $table->json('symbols_tested')->nullable();
            $table->json('timeframes_tested')->nullable();
            $table->json('detailed_results')->nullable()->comment('Trade-by-trade data');
            $table->timestamps();

            $table->index(['template_type', 'template_id'], 'tmpl_backtest_type_id_idx');
            $table->index('win_rate', 'tmpl_backtest_winrate_idx');
            $table->index('net_profit_percent', 'tmpl_backtest_profit_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('template_backtests');
    }
}
