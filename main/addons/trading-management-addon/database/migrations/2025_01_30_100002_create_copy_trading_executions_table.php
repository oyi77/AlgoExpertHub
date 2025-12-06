<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCopyTradingExecutionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('copy_trading_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trader_position_id')->comment('Original ExecutionPosition from trader');
            $table->unsignedBigInteger('trader_id');
            $table->unsignedBigInteger('follower_id');
            $table->unsignedBigInteger('subscription_id');
            $table->unsignedBigInteger('follower_position_id')->nullable()->comment('Created ExecutionPosition for follower');
            $table->unsignedBigInteger('follower_connection_id');
            $table->timestamp('copied_at')->nullable();
            $table->enum('status', ['pending', 'executed', 'failed', 'closed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->decimal('risk_multiplier_used', 10, 4)->nullable();
            $table->decimal('original_quantity', 20, 8);
            $table->decimal('copied_quantity', 20, 8);
            $table->json('calculation_details')->nullable()->comment('Details about how quantity was calculated');
            $table->timestamps();

            $table->foreign('trader_position_id')->references('id')->on('execution_positions')->onDelete('cascade');
            $table->foreign('trader_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('follower_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('subscription_id')->references('id')->on('copy_trading_subscriptions')->onDelete('cascade');
            $table->foreign('follower_position_id')->references('id')->on('execution_positions')->onDelete('set null');
            $table->foreign('follower_connection_id')->references('id')->on('execution_connections')->onDelete('cascade');

            $table->index('trader_position_id');
            $table->index('trader_id');
            $table->index('follower_id');
            $table->index('subscription_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('copy_trading_executions');
    }
}
