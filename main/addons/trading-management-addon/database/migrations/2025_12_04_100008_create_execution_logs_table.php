<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExecutionLogsTable extends Migration
{
    public function up()
    {
        // Skip if table already exists (created by trading-execution-engine-addon migration)
        if (Schema::hasTable('execution_logs')) {
            return;
        }

        Schema::create('execution_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('execution_connection_id');
            $table->unsignedBigInteger('signal_id');
            $table->string('order_id')->nullable();
            $table->string('symbol');
            $table->enum('side', ['buy', 'sell']);
            $table->decimal('lot_size', 10, 2);
            $table->decimal('entry_price', 20, 8)->nullable();
            $table->decimal('stop_loss', 20, 8)->nullable();
            $table->decimal('take_profit', 20, 8)->nullable();
            $table->enum('status', ['pending', 'filled', 'rejected', 'cancelled'])->default('pending');
            $table->text('error_message')->nullable();
            $table->json('order_data')->nullable();
            $table->timestamps();

            $table->foreign('execution_connection_id')->references('id')->on('execution_connections')->onDelete('cascade');
            $table->foreign('signal_id')->references('id')->on('signals')->onDelete('cascade');
            
            $table->index('execution_connection_id');
            $table->index('signal_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('execution_logs');
    }
}

