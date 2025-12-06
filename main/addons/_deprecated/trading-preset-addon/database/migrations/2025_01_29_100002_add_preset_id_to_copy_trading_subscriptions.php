<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPresetIdToCopyTradingSubscriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if table exists before altering
        if (!Schema::hasTable('copy_trading_subscriptions')) {
            return; // Table will be created by copy-trading-addon migration
        }
        
        Schema::table('copy_trading_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('preset_id')->nullable()->after('copy_settings');
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
        Schema::table('copy_trading_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['preset_id']);
            $table->dropIndex(['preset_id']);
            $table->dropColumn('preset_id');
        });
    }
}

