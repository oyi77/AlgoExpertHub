<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create stream_subscriptions table
 * 
 * Tracks which bots/connections subscribe to which streams
 */
class CreateStreamSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('stream_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained('metaapi_streams')->onDelete('cascade');
            $table->enum('subscriber_type', ['bot', 'connection'])->comment('Type of subscriber');
            $table->unsignedBigInteger('subscriber_id')->comment('ID of trading_bot or execution_connection');
            $table->timestamps();

            // Unique constraint: one subscription per stream/subscriber
            $table->unique(['stream_id', 'subscriber_type', 'subscriber_id'], 'unique_subscription');
            
            // Indexes
            $table->index('stream_id');
            $table->index(['subscriber_type', 'subscriber_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stream_subscriptions');
    }
}
