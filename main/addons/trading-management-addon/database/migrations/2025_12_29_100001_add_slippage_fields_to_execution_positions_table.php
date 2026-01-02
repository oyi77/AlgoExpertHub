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
        Schema::table('execution_positions', function (Blueprint $table) {
            $table->decimal('slippage_pips', 10, 4)->nullable()->after('entry_price')->comment('Actual slippage on entry in pips');
            $table->decimal('execution_price', 20, 8)->nullable()->after('slippage_pips')->comment('Actual execution price from exchange (may differ from entry_price)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('execution_positions', function (Blueprint $table) {
            $table->dropColumn(['slippage_pips', 'execution_price']);
        });
    }
};

