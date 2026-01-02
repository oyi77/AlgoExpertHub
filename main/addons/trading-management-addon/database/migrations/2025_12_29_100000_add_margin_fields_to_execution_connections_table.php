<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('execution_connections', function (Blueprint $table) {
            $table->integer('leverage')->default(100)->after('settings')->comment('Account leverage (e.g., 100 for 1:100)');
            $table->decimal('margin_call_threshold', 5, 2)->default(100.0)->after('leverage')->comment('Margin level % for margin call');
            $table->decimal('liquidation_threshold', 5, 2)->default(50.0)->after('margin_call_threshold')->comment('Margin level % for liquidation warning');
            $table->decimal('max_margin_usage_pct', 5, 2)->default(80.0)->after('liquidation_threshold')->comment('Maximum margin usage allowed (%)');
            $table->integer('max_open_positions')->default(5)->after('max_margin_usage_pct')->comment('Maximum open positions per connection');
            $table->integer('max_positions_per_symbol')->default(1)->after('max_open_positions')->comment('Maximum positions per symbol');
            $table->boolean('circuit_breaker_enabled')->default(true)->after('max_positions_per_symbol')->comment('Enable circuit breaker for consecutive failures');
            $table->integer('max_consecutive_failures')->default(5)->after('circuit_breaker_enabled')->comment('Maximum consecutive failures before halting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('execution_connections', function (Blueprint $table) {
            $table->dropColumn([
                'leverage',
                'margin_call_threshold',
                'liquidation_threshold',
                'max_margin_usage_pct',
                'max_open_positions',
                'max_positions_per_symbol',
                'circuit_breaker_enabled',
                'max_consecutive_failures',
            ]);
        });
    }
};

