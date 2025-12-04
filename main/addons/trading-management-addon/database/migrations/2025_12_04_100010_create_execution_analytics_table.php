<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExecutionAnalyticsTable extends Migration
{
    public function up()
    {
        Schema::create('execution_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('execution_connection_id');
            $table->date('date');
            $table->integer('total_trades')->default(0);
            $table->integer('winning_trades')->default(0);
            $table->integer('losing_trades')->default(0);
            $table->decimal('win_rate', 5, 2)->default(0);
            $table->decimal('total_profit', 20, 8)->default(0);
            $table->decimal('total_loss', 20, 8)->default(0);
            $table->decimal('net_profit', 20, 8)->default(0);
            $table->decimal('profit_factor', 10, 4)->default(0);
            $table->decimal('max_drawdown', 20, 8)->default(0);
            $table->decimal('sharpe_ratio', 10, 4)->nullable();
            $table->timestamps();

            $table->foreign('execution_connection_id')->references('id')->on('execution_connections')->onDelete('cascade');
            
            $table->index('execution_connection_id');
            $table->index('date');
            $table->unique(['execution_connection_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('execution_analytics');
    }
}

