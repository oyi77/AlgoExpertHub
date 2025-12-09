<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add expert_advisor_id to trading_bots table
 * 
 * Adds support for MT4/MT5 Expert Advisor integration
 */
class AddExpertAdvisorIdToTradingBotsTable extends Migration
{
    public function up()
    {
        Schema::table('trading_bots', function (Blueprint $table) {
            $table->unsignedBigInteger('expert_advisor_id')->nullable()->after('ai_model_profile_id')
                ->comment('FK to expert_advisors (optional, for MT4/MT5 EA integration)');
            
            if (Schema::hasTable('expert_advisors')) {
                $table->foreign('expert_advisor_id')->references('id')->on('expert_advisors')->onDelete('set null');
            }
            
            $table->index('expert_advisor_id');
        });
    }

    public function down()
    {
        Schema::table('trading_bots', function (Blueprint $table) {
            $table->dropForeign(['expert_advisor_id']);
            $table->dropIndex(['expert_advisor_id']);
            $table->dropColumn('expert_advisor_id');
        });
    }
}
