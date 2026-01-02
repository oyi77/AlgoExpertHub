<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add trading_bot_created to user_onboarding_progress
 * 
 * Adds tracking for trading bot creation step in onboarding
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('user_onboarding_progress')) {
            Schema::table('user_onboarding_progress', function (Blueprint $table) {
                if (!Schema::hasColumn('user_onboarding_progress', 'trading_bot_created')) {
                    $table->boolean('trading_bot_created')->default(false)->after('trading_preset_created');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('user_onboarding_progress')) {
            Schema::table('user_onboarding_progress', function (Blueprint $table) {
                if (Schema::hasColumn('user_onboarding_progress', 'trading_bot_created')) {
                    $table->dropColumn('trading_bot_created');
                }
            });
        }
    }
};
