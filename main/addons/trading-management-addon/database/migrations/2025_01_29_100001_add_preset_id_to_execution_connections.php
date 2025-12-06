<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPresetIdToExecutionConnections extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('execution_connections', function (Blueprint $table) {
            $table->unsignedBigInteger('preset_id')->nullable()->after('settings');
            $table->index('preset_id');
            $table->foreign('preset_id')->references('id')->on('trading_presets')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('execution_connections', function (Blueprint $table) {
            $table->dropForeign(['preset_id']);
            $table->dropIndex(['preset_id']);
            $table->dropColumn('preset_id');
        });
    }
}

