<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create backtest_reports table for aggregated backtesting performance reports
     */
    public function up(): void
    {
        Schema::create('backtest_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('period')->nullable(); // daily, weekly, monthly, custom
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('total_backtests')->default(0);
            $table->integer('total_trades')->default(0);
            $table->integer('winning_trades')->default(0);
            $table->integer('losing_trades')->default(0);
            $table->decimal('total_profit', 20, 8)->default(0);
            $table->decimal('total_loss', 20, 8)->default(0);
            $table->decimal('total_return', 8, 4)->default(0);
            $table->decimal('avg_win_rate', 5, 2)->default(0);
            $table->decimal('avg_loss', 5, 2)->default(0);
            $table->decimal('profit_factor', 8, 4)->default(0);
            $table->decimal('max_drawdown', 20, 8)->default(0);
            $table->decimal('max_profit', 20, 8)->default(0);
            $table->decimal('max_loss', 20, 8)->default(0);
            $table->decimal('avg_win', 8, 2)->default(0);
            $table->decimal('avg_loss_amount', 8, 2)->default(0);
            $table->integer('best_win_streak')->default(0);
            $table->integer('worst_loss_streak')->default(0);
            $table->json('details')->nullable(); // Store additional metrics as JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backtest_reports');
    }
};
