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
        Schema::table('execution_connections', function (Blueprint $table) {
            // Add connection_type column (CRYPTO_EXCHANGE or FX_BROKER)
            if (!Schema::hasColumn('execution_connections', 'connection_type')) {
                $table->enum('connection_type', ['CRYPTO_EXCHANGE', 'FX_BROKER'])->nullable()->after('type');
            }
            
            // Add provider column (metaapi, binance, etc.)
            if (!Schema::hasColumn('execution_connections', 'provider')) {
                $table->string('provider')->nullable()->after('exchange_name');
            }
            
            // Add data_fetching_enabled flag
            if (!Schema::hasColumn('execution_connections', 'data_fetching_enabled')) {
                $table->boolean('data_fetching_enabled')->default(false)->after('is_active');
            }
            
            // Add trade_execution_enabled flag
            if (!Schema::hasColumn('execution_connections', 'trade_execution_enabled')) {
                $table->boolean('trade_execution_enabled')->default(false)->after('data_fetching_enabled');
            }
            
            // Add data_settings JSON column
            if (!Schema::hasColumn('execution_connections', 'data_settings')) {
                $table->json('data_settings')->nullable()->after('settings');
            }
            
            // Add execution_settings JSON column
            if (!Schema::hasColumn('execution_connections', 'execution_settings')) {
                $table->json('execution_settings')->nullable()->after('data_settings');
            }
            
            // Add last_data_fetch_at timestamp
            if (!Schema::hasColumn('execution_connections', 'last_data_fetch_at')) {
                $table->timestamp('last_data_fetch_at')->nullable()->after('last_used_at');
            }
            
            // Add last_trade_execution_at timestamp
            if (!Schema::hasColumn('execution_connections', 'last_trade_execution_at')) {
                $table->timestamp('last_trade_execution_at')->nullable()->after('last_data_fetch_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('execution_connections', function (Blueprint $table) {
            $table->dropColumn([
                'connection_type',
                'provider',
                'data_fetching_enabled',
                'trade_execution_enabled',
                'data_settings',
                'execution_settings',
                'last_data_fetch_at',
                'last_trade_execution_at',
            ]);
        });
    }
};
