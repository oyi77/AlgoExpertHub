<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTraderProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('trader_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('display_name');
            $table->text('bio')->nullable();
            $table->string('avatar_url')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('accepts_followers')->default(true);
            $table->integer('max_followers')->nullable();
            $table->decimal('subscription_price', 10, 2)->default(0)->comment('0=free');
            $table->string('currency', 3)->default('USD');
            $table->integer('total_followers')->default(0);
            $table->decimal('total_profit_percent', 10, 2)->default(0);
            $table->decimal('win_rate', 5, 2)->default(0);
            $table->decimal('avg_monthly_return', 10, 2)->default(0);
            $table->decimal('max_drawdown', 10, 2)->default(0);
            $table->integer('trades_count')->default(0);
            $table->boolean('verified')->default(false);
            $table->json('trading_style')->nullable()->comment('Markets, strategies, timeframes');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['is_public', 'verified', 'total_profit_percent'], 'trader_public_ver_profit_idx');
            $table->index('win_rate', 'trader_winrate_idx');
            $table->index('total_followers', 'trader_followers_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trader_profiles');
    }
}
