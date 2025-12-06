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
        // Only proceed if execution_logs table exists
        if (!Schema::hasTable('execution_logs')) {
            return;
        }

        Schema::table('execution_logs', function (Blueprint $table) {
            // Check if columns already exist before adding
            if (!Schema::hasColumn('execution_logs', 'slippage')) {
                $table->decimal('slippage', 8, 4)->nullable()->after('entry_price')->comment('Actual slippage in pips');
            }
            if (!Schema::hasColumn('execution_logs', 'latency_ms')) {
                $table->unsignedInteger('latency_ms')->nullable()->after('slippage')->comment('Time from signal received to execution');
            }
            if (!Schema::hasColumn('execution_logs', 'market_atr')) {
                $table->decimal('market_atr', 10, 4)->nullable()->after('latency_ms')->comment('ATR value at execution time');
            }
            if (!Schema::hasColumn('execution_logs', 'trading_session')) {
                $table->enum('trading_session', ['TOKYO', 'LONDON', 'NEW_YORK', 'ASIAN', 'OVERLAP'])->nullable()->after('market_atr')->comment('Trading session');
            }
            if (!Schema::hasColumn('execution_logs', 'day_of_week')) {
                $table->tinyInteger('day_of_week')->nullable()->after('trading_session')->comment('Day of week 1-7');
            }
            if (!Schema::hasColumn('execution_logs', 'volatility_index')) {
                $table->decimal('volatility_index', 8, 4)->nullable()->after('day_of_week')->comment('Calculated volatility metric');
            }
            if (!Schema::hasColumn('execution_logs', 'signal_provider_id')) {
                $table->string('signal_provider_id', 255)->nullable()->after('volatility_index')->comment('channel_source_id or user_id');
            }
            if (!Schema::hasColumn('execution_logs', 'signal_provider_type')) {
                $table->enum('signal_provider_type', ['channel_source', 'user'])->nullable()->after('signal_provider_id')->comment('Signal provider type');
            }
        });

        // Note: index creation that inspects SHOW INDEXES can fail on missing tables in some environments.
        // To keep the migration safe on hosts where execution_logs belum ada, index penunjang bisa ditambahkan manual nanti.
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('execution_logs')) {
            return;
        }

        Schema::table('execution_logs', function (Blueprint $table) {
            // Drop columns if they exist
            $columns = ['slippage', 'latency_ms', 'market_atr', 'trading_session', 'day_of_week', 'volatility_index', 'signal_provider_id', 'signal_provider_type'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('execution_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

