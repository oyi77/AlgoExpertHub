<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSignalAnalyticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('signal_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('signal_id');
            $table->unsignedBigInteger('channel_source_id')->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->comment('User yang menerima signal');
            
            // Signal metrics
            $table->string('currency_pair')->nullable();
            $table->string('direction')->nullable();
            $table->decimal('open_price', 28, 8)->nullable();
            $table->decimal('sl', 28, 8)->nullable();
            $table->decimal('tp', 28, 8)->nullable();
            
            // Performance metrics (akan diupdate jika ada trade execution)
            $table->decimal('actual_open_price', 28, 8)->nullable();
            $table->decimal('actual_close_price', 28, 8)->nullable();
            $table->decimal('profit_loss', 28, 8)->nullable()->default(0);
            $table->decimal('pips', 18, 2)->nullable()->default(0);
            $table->enum('trade_status', ['pending', 'open', 'closed', 'cancelled'])->default('pending');
            
            // Timestamps
            $table->timestamp('signal_received_at');
            $table->timestamp('signal_published_at')->nullable();
            $table->timestamp('trade_opened_at')->nullable();
            $table->timestamp('trade_closed_at')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable()->comment('Additional data: parsing confidence, pattern used, etc.');
            
            $table->timestamps();

            $table->foreign('signal_id')->references('id')->on('signals')->onDelete('cascade');
            $table->foreign('channel_source_id')->references('id')->on('channel_sources')->onDelete('set null');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index('signal_id');
            $table->index('channel_source_id');
            $table->index('plan_id');
            $table->index('user_id');
            $table->index('trade_status');
            $table->index('signal_received_at');
            $table->index('currency_pair');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('signal_analytics');
    }
}

