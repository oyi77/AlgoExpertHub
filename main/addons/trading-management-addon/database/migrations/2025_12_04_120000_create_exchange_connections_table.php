<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExchangeConnectionsTable extends Migration
{
    public function up()
    {
        Schema::create('exchange_connections', function (Blueprint $table) {
            $table->id();
            
            // Ownership
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->boolean('is_admin_owned')->default(false);
            
            // Connection Details
            $table->string('name');
            $table->enum('connection_type', ['CRYPTO_EXCHANGE', 'FX_BROKER'])->comment('Exchange or Broker');
            $table->string('provider')->comment('binance, kraken, mt4, etc');
            $table->json('credentials')->comment('Encrypted API keys');
            
            // Feature Toggles - What this connection is used for
            $table->boolean('data_fetching_enabled')->default(false)->comment('Use for market data');
            $table->boolean('trade_execution_enabled')->default(false)->comment('Use for trading');
            
            // Status & Health
            $table->enum('status', ['pending', 'connected', 'error'])->default('pending');
            $table->boolean('is_active')->default(true);
            $table->text('last_error')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_data_fetch_at')->nullable();
            $table->timestamp('last_trade_execution_at')->nullable();
            
            // Trading Config (if execution enabled)
            $table->unsignedBigInteger('preset_id')->nullable()->comment('Trading preset for execution');
            $table->json('execution_settings')->nullable()->comment('Position sizing, etc');
            
            // Data Config (if data fetching enabled)
            $table->json('data_settings')->nullable()->comment('Symbols, timeframes to fetch');
            
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('preset_id')->references('id')->on('trading_presets')->onDelete('set null');
            
            $table->index(['user_id', 'admin_id', 'is_admin_owned'], 'exc_owner_idx');
            $table->index(['status', 'is_active'], 'exc_status_idx');
            $table->index(['data_fetching_enabled', 'trade_execution_enabled'], 'exc_features_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('exchange_connections');
    }
}

