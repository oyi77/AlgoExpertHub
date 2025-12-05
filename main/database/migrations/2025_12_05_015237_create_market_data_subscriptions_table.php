<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketDataSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('market_data_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 50);
            $table->enum('timeframe', ['M1', 'M5', 'M15', 'M30', 'H1', 'H4', 'D1', 'W1', 'MN']);
            $table->enum('subscriber_type', ['bot', 'user', 'backtest', 'system'])->default('user');
            $table->unsignedBigInteger('subscriber_id');
            $table->timestamp('last_access')->nullable();
            $table->integer('access_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['symbol', 'timeframe', 'is_active'], 'mkt_data_sub_sym_tf_active_idx');
            $table->index(['subscriber_type', 'subscriber_id'], 'mkt_data_sub_type_id_idx');
            $table->index('last_access', 'mkt_data_sub_access_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('market_data_subscriptions');
    }
}
