<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExecutionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('execution_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('signal_id');
            $table->unsignedBigInteger('connection_id');
            $table->enum('execution_type', ['market', 'limit'])->default('market');
            $table->string('order_id')->nullable()->comment('Exchange/broker order ID');
            $table->string('symbol');
            $table->enum('direction', ['buy', 'sell']);
            $table->decimal('quantity', 20, 8);
            $table->decimal('entry_price', 20, 8)->nullable();
            $table->decimal('sl_price', 20, 8)->nullable();
            $table->decimal('tp_price', 20, 8)->nullable();
            $table->enum('status', ['pending', 'executed', 'failed', 'cancelled', 'partial'])->default('pending');
            $table->timestamp('executed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('response_data')->nullable()->comment('Raw response from exchange/broker');
            $table->timestamps();

            $table->foreign('signal_id')->references('id')->on('signals')->onDelete('cascade');
            $table->foreign('connection_id')->references('id')->on('execution_connections')->onDelete('cascade');

            $table->index('signal_id');
            $table->index('connection_id');
            $table->index('status');
            $table->index('order_id');
            $table->index('executed_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('execution_logs');
    }
}

