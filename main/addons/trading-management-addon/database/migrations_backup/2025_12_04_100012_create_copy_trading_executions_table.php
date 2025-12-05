<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCopyTradingExecutionsTable extends Migration
{
    public function up()
    {
        // Skip if table already exists (created by copy-trading-addon migration)
        if (Schema::hasTable('copy_trading_executions')) {
            return;
        }

        Schema::create('copy_trading_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_id');
            $table->unsignedBigInteger('trader_execution_log_id')->comment('Original trader execution');
            $table->unsignedBigInteger('follower_execution_log_id')->comment('Copied execution');
            $table->decimal('original_lot_size', 20, 8);
            $table->decimal('copied_lot_size', 20, 8);
            $table->decimal('multiplier_applied', 8, 4);
            $table->enum('status', ['pending', 'copied', 'failed', 'skipped']);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('subscription_id')->references('id')->on('copy_trading_subscriptions')->onDelete('cascade');
            $table->foreign('trader_execution_log_id')->references('id')->on('execution_logs')->onDelete('cascade');
            $table->foreign('follower_execution_log_id')->references('id')->on('execution_logs')->onDelete('cascade');

            $table->index('subscription_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('copy_trading_executions');
    }
}

