<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCopyTradingSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('copy_trading_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trader_id')->comment('User who is being copied');
            $table->unsignedBigInteger('follower_id')->comment('User who is copying');
            $table->enum('copy_mode', ['easy', 'advanced'])->default('easy')->comment('Copy trading mode');
            $table->decimal('risk_multiplier', 10, 4)->default(1.0)->comment('Position size multiplier for easy mode (0.1 to 10.0)');
            $table->decimal('max_position_size', 20, 8)->nullable()->comment('Max USD per copied trade');
            $table->unsignedBigInteger('connection_id')->comment('Which ExecutionConnection to use for copying');
            $table->json('copy_settings')->nullable()->comment('Advanced mode settings: method, percentage, fixed_quantity, min_quantity, max_quantity');
            $table->boolean('is_active')->default(true);
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->json('stats')->nullable()->comment('Copied trades count, total PnL, etc.');
            $table->timestamps();

            $table->foreign('trader_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('follower_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('connection_id')->references('id')->on('execution_connections')->onDelete('cascade');
            
            $table->unique(['trader_id', 'follower_id']);
            $table->index('trader_id');
            $table->index('follower_id');
            $table->index('is_active');
            $table->index('copy_mode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('copy_trading_subscriptions');
    }
}
