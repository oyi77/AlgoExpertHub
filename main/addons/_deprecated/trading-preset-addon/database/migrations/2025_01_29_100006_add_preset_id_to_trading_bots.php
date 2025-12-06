<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPresetIdToTradingBots extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if trading_bots table exists
        if (Schema::hasTable('trading_bots')) {
            Schema::table('trading_bots', function (Blueprint $table) {
                if (!Schema::hasColumn('trading_bots', 'preset_id')) {
                    $table->unsignedBigInteger('preset_id')->nullable()->after('id');
                    $table->index('preset_id');
                    $table->foreign('preset_id')
                        ->references('id')
                        ->on('trading_presets')
                        ->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('trading_bots')) {
            Schema::table('trading_bots', function (Blueprint $table) {
                if (Schema::hasColumn('trading_bots', 'preset_id')) {
                    $table->dropForeign(['preset_id']);
                    $table->dropIndex(['preset_id']);
                    $table->dropColumn('preset_id');
                }
            });
        }
    }
}

