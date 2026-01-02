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
        Schema::table('backtests', function (Blueprint $table) {
            $table->enum('slippage_model', ['none', 'fixed', 'volatility'])->default('fixed')->after('status')->comment('Slippage modeling approach');
            $table->decimal('slippage_pips', 10, 4)->nullable()->after('slippage_model')->comment('Fixed slippage if model is fixed');
            $table->boolean('spread_cost_enabled')->default(true)->after('slippage_pips')->comment('Include spread costs');
            $table->boolean('partial_fills_enabled')->default(false)->after('spread_cost_enabled')->comment('Model partial fills');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backtests', function (Blueprint $table) {
            $table->dropColumn([
                'slippage_model',
                'slippage_pips',
                'spread_cost_enabled',
                'partial_fills_enabled',
            ]);
        });
    }
};

