<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('name');
            $table->string('type'); // mtapi, ccxt_crypto, custom_api
            $table->string('provider'); // binance, mt4_account_123
            $table->text('credentials'); // encrypted
            $table->json('config')->nullable();
            $table->string('status')->default('inactive'); // active, inactive, error
            $table->boolean('is_active')->default(false);
            $table->boolean('is_admin_owned')->default(false);
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('settings')->nullable(); // symbols, timeframes to fetch
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
        });

        Schema::create('market_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('data_connection_id');
            $table->string('symbol'); // EURUSD, BTC/USDT
            $table->string('timeframe'); // M1, M5, H1, D1
            $table->unsignedBigInteger('timestamp'); // Candle open time (Unix ms)
            $table->decimal('open', 24, 8);
            $table->decimal('high', 24, 8);
            $table->decimal('low', 24, 8);
            $table->decimal('close', 24, 8);
            $table->decimal('volume', 24, 8)->default(0);
            $table->string('provider'); // mtapi, ccxt
            $table->timestamps();

            $table->foreign('data_connection_id')->references('id')->on('data_connections')->onDelete('cascade');
            
            // Unique index to prevent duplicates
            $table->unique(['data_connection_id', 'symbol', 'timeframe', 'timestamp'], 'market_data_unique_index');
            // Index for fast querying
            $table->index(['symbol', 'timeframe', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('market_data');
        Schema::dropIfExists('data_connections');
    }
};
