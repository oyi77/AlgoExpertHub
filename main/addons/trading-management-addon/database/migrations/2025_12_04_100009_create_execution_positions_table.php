<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExecutionPositionsTable extends Migration
{
    public function up()
    {
        Schema::create('execution_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('signal_id');
            $table->unsignedBigInteger('execution_connection_id')->comment('Renamed from connection_id');
            $table->unsignedBigInteger('execution_log_id');
            $table->string('order_id')->nullable();
            $table->string('symbol');
            $table->enum('direction', ['buy', 'sell']);
            $table->decimal('quantity', 20, 8);
            $table->decimal('entry_price', 20, 8);
            $table->decimal('current_price', 20, 8)->nullable();
            $table->decimal('sl_price', 20, 8)->nullable();
            $table->decimal('tp_price', 20, 8)->nullable();
            $table->enum('status', ['open', 'closed', 'liquidated'])->default('open');
            $table->decimal('pnl', 20, 8)->default(0);
            $table->decimal('pnl_percentage', 10, 4)->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->enum('closed_reason', ['tp', 'sl', 'manual', 'liquidation'])->nullable();
            $table->timestamp('last_price_update_at')->nullable();
            $table->timestamps();

            $table->foreign('signal_id')->references('id')->on('signals')->onDelete('cascade');
            $table->foreign('execution_connection_id')->references('id')->on('execution_connections')->onDelete('cascade');
            $table->foreign('execution_log_id')->references('id')->on('execution_logs')->onDelete('cascade');

            $table->index('signal_id');
            $table->index('execution_connection_id');
            $table->index('execution_log_id');
            $table->index('status');
            $table->index('order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('execution_positions');
    }
}

