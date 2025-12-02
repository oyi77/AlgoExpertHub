<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChannelSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channel_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->enum('type', ['telegram', 'telegram_mtproto', 'api', 'web_scrape', 'rss'])->default('telegram');
            $table->json('config')->comment('Encrypted credentials, URLs, selectors, etc.');
            $table->enum('status', ['active', 'paused', 'error', 'pending'])->default('active');
            $table->timestamp('last_processed_at')->nullable();
            $table->unsignedInteger('error_count')->default(0);
            $table->text('last_error')->nullable();
            $table->unsignedInteger('auto_publish_confidence_threshold')->default(90)->comment('0-100, signals with confidence >= this are auto-published');
            $table->unsignedBigInteger('default_plan_id')->nullable();
            $table->unsignedBigInteger('default_market_id')->nullable();
            $table->unsignedBigInteger('default_timeframe_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('default_plan_id')->references('id')->on('plans')->onDelete('set null');
            $table->foreign('default_market_id')->references('id')->on('markets')->onDelete('set null');
            $table->foreign('default_timeframe_id')->references('id')->on('time_frames')->onDelete('set null');

            $table->index('user_id');
            $table->index('status');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channel_sources');
    }
}
