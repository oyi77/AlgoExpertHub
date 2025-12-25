<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('backtests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name', 255);
            $table->string('symbol', 50);
            $table->string('timeframe', 10);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('initial_balance', 20, 8)->default(10000);
            $table->decimal('final_balance', 20, 8)->default(0);
            $table->decimal('total_return', 10, 4)->default(0);
            $table->decimal('win_rate', 5, 2)->default(0);
            $table->decimal('max_drawdown', 10, 4)->default(0);
            $table->decimal('profit_factor', 10, 4)->default(0);
            $table->integer('total_trades')->default(0);
            $table->integer('winning_trades')->default(0);
            $table->integer('losing_trades')->default(0);
            $table->decimal('average_win', 20, 8)->default(0);
            $table->decimal('average_loss', 20, 8)->default(0);
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index(['symbol', 'timeframe']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('backtests');
    }
};

