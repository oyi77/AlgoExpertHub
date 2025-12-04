<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create copy_trading_subscriptions table
 * 
 * Migrated from copy-trading-addon
 * Links to execution_connections and trading_presets
 */
class CreateCopyTradingSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('copy_trading_subscriptions', function (Blueprint $table) {
            $table->id();
            
            // Parties
            $table->unsignedBigInteger('trader_id')->comment('User being copied');
            $table->unsignedBigInteger('follower_id')->comment('User copying');
            
            // Copy Configuration
            $table->enum('copy_mode', ['easy', 'advanced'])->default('easy');
            $table->decimal('risk_multiplier', 8, 4)->default(1.0)->comment('Multiply trader lot by this');
            $table->decimal('max_position_size', 20, 8)->nullable();
            $table->json('copy_settings')->nullable()->comment('Advanced copy settings');
            
            // Links
            $table->unsignedBigInteger('execution_connection_id')->comment('Follower execution connection');
            $table->unsignedBigInteger('preset_id')->nullable()->comment('Follower risk preset');
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            
            // Stats
            $table->json('stats')->nullable()->comment('Copy trading statistics');
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('trader_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('follower_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('execution_connection_id')->references('id')->on('execution_connections')->onDelete('cascade');
            $table->foreign('preset_id')->references('id')->on('trading_presets')->onDelete('set null');

            // Indexes
            $table->index('trader_id');
            $table->index('follower_id');
            $table->index('execution_connection_id');
            $table->index('is_active');
            $table->unique(['trader_id', 'follower_id', 'execution_connection_id'], 'unique_subscription');
        });
    }

    public function down()
    {
        Schema::dropIfExists('copy_trading_subscriptions');
    }
}

