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
        // Only proceed if execution_positions table exists
        if (!Schema::hasTable('execution_positions')) {
            return;
        }

        Schema::table('execution_positions', function (Blueprint $table) {
            // Check if columns already exist before adding
            if (!Schema::hasColumn('execution_positions', 'predicted_slippage')) {
                $table->decimal('predicted_slippage', 8, 4)->nullable()->after('entry_price')->comment('Predicted slippage at entry');
            }
            if (!Schema::hasColumn('execution_positions', 'performance_score_at_entry')) {
                $table->decimal('performance_score_at_entry', 5, 2)->nullable()->after('predicted_slippage')->comment('SP performance score at entry');
            }
            if (!Schema::hasColumn('execution_positions', 'srm_adjusted_lot')) {
                $table->decimal('srm_adjusted_lot', 10, 4)->nullable()->after('quantity')->comment('SRM-adjusted lot size');
            }
            if (!Schema::hasColumn('execution_positions', 'srm_sl_buffer')) {
                $table->decimal('srm_sl_buffer', 8, 4)->nullable()->after('sl_price')->comment('SRM-added SL buffer in pips');
            }
            if (!Schema::hasColumn('execution_positions', 'srm_adjustment_reason')) {
                $table->text('srm_adjustment_reason')->nullable()->after('srm_sl_buffer')->comment('Reason for SRM adjustment (JSON)');
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
        if (!Schema::hasTable('execution_positions')) {
            return;
        }

        Schema::table('execution_positions', function (Blueprint $table) {
            $columns = ['predicted_slippage', 'performance_score_at_entry', 'srm_adjusted_lot', 'srm_sl_buffer', 'srm_adjustment_reason'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('execution_positions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

