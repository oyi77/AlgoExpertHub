<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChannelMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channel_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_source_id');
            $table->text('raw_message');
            $table->string('message_hash', 64)->comment('SHA256 hash for duplicate detection');
            $table->json('parsed_data')->nullable()->comment('Parsed signal data');
            $table->unsignedBigInteger('signal_id')->nullable();
            $table->enum('status', ['pending', 'processed', 'failed', 'duplicate', 'manual_review'])->default('pending');
            $table->unsignedInteger('confidence_score')->nullable()->comment('0-100 parsing confidence');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('processing_attempts')->default(0);
            $table->timestamps();

            $table->foreign('channel_source_id')->references('id')->on('channel_sources')->onDelete('cascade');
            $table->foreign('signal_id')->references('id')->on('signals')->onDelete('set null');

            $table->index('channel_source_id');
            $table->index('message_hash');
            $table->index('status');
            $table->index('signal_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channel_messages');
    }
}

