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
        if (!Schema::hasTable('execution_logs')) {
            return;
        }

        Schema::table('execution_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('execution_logs', 'execution_price')) {
                $table->decimal('execution_price', 20, 8)->nullable()->after('entry_price')->comment('Actual execution price from exchange (may differ from entry_price)');
            }
            if (!Schema::hasColumn('execution_logs', 'slippage_pips')) {
                $table->decimal('slippage_pips', 10, 4)->nullable()->after('execution_price')->comment('Actual slippage on execution in pips');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('execution_logs')) {
            return;
        }

        Schema::table('execution_logs', function (Blueprint $table) {
            if (Schema::hasColumn('execution_logs', 'execution_price')) {
                $table->dropColumn('execution_price');
            }
            if (Schema::hasColumn('execution_logs', 'slippage_pips')) {
                $table->dropColumn('slippage_pips');
            }
        });
    }
};

