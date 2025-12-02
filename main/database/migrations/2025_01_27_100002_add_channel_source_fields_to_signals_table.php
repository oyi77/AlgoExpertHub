<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChannelSourceFieldsToSignalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('signals', function (Blueprint $table) {
            $table->unsignedBigInteger('channel_source_id')->nullable()->after('id');
            $table->boolean('auto_created')->default(0)->after('is_published');
            $table->string('message_hash', 64)->nullable()->after('auto_created');

            $table->foreign('channel_source_id')->references('id')->on('channel_sources')->onDelete('set null');
            $table->index('channel_source_id');
            $table->index('auto_created');
            $table->index('message_hash');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('signals', function (Blueprint $table) {
            $table->dropForeign(['channel_source_id']);
            $table->dropIndex(['channel_source_id']);
            $table->dropIndex(['auto_created']);
            $table->dropIndex(['message_hash']);
            $table->dropColumn(['channel_source_id', 'auto_created', 'message_hash']);
        });
    }
}

