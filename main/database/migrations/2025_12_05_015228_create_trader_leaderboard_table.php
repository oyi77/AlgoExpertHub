<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTraderLeaderboardTable extends Migration
{
    public function up()
    {
        Schema::create('trader_leaderboard', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trader_id');
            $table->integer('rank')->default(0);
            $table->enum('timeframe', ['daily', 'weekly', 'monthly', 'all_time'])->default('all_time');
            $table->decimal('profit_percent', 10, 2)->default(0);
            $table->decimal('win_rate', 5, 2)->default(0);
            $table->decimal('sharpe_ratio', 10, 4)->nullable();
            $table->decimal('roi', 10, 2)->nullable();
            $table->integer('total_trades')->default(0);
            $table->integer('followers_gained')->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->foreign('trader_id')->references('id')->on('trader_profiles')->onDelete('cascade');
            $table->unique(['trader_id', 'timeframe'], 'trader_lead_trader_tf_uniq');
            $table->index(['timeframe', 'rank'], 'trader_lead_tf_rank_idx');
            $table->index(['timeframe', 'profit_percent'], 'trader_lead_tf_profit_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trader_leaderboard');
    }
}
