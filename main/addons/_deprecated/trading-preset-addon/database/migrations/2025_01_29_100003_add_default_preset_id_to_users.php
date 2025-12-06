<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultPresetIdToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('default_preset_id')->nullable()->after('id');
            $table->index('default_preset_id');
            $table->foreign('default_preset_id')->references('id')->on('trading_presets')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_preset_id']);
            $table->dropIndex(['default_preset_id']);
            $table->dropColumn('default_preset_id');
        });
    }
}

