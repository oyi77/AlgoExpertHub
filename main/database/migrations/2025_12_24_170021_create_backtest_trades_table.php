<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('backtest_trades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('backtest_id');
            $table->timestamp('entry_time');
            $table->timestamp('exit_time')->nullable();
            $table->decimal('entry_price', 20, 8);
            $table->decimal('exit_price', 20, 8)->nullable();
            $table->enum('direction', ['buy', 'sell', 'long', 'short']);
            $table->decimal('quantity', 20, 8);
            $table->decimal('profit_loss', 20, 8)->default(0);
            $table->decimal('profit_loss_percent', 10, 4)->default(0);
            $table->enum('status', ['open', 'closed', 'stopped'])->default('open');
            $table->decimal('stop_loss', 20, 8)->nullable();
            $table->decimal('take_profit', 20, 8)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('backtest_id')->references('id')->on('backtests')->onDelete('cascade');
            $table->index('backtest_id');
            $table->index(['backtest_id', 'status']);
            $table->index('entry_time');
        });
    }

    public function down()
    {
        Schema::dropIfExists('backtest_trades');
    }
};

