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
        Schema::table('execution_positions', function (Blueprint $table) {
            if (!Schema::hasColumn('execution_positions', 'trailing_stop_enabled')) {
                $table->boolean('trailing_stop_enabled')->default(false)->after('sl_price');
            }
            if (!Schema::hasColumn('execution_positions', 'trailing_stop_distance')) {
                $table->decimal('trailing_stop_distance', 10, 4)->nullable()->after('trailing_stop_enabled')->comment('Distance in price units or pips');
            }
            if (!Schema::hasColumn('execution_positions', 'trailing_stop_percentage')) {
                $table->decimal('trailing_stop_percentage', 5, 2)->nullable()->after('trailing_stop_distance')->comment('Distance as percentage');
            }
            if (!Schema::hasColumn('execution_positions', 'highest_price')) {
                $table->decimal('highest_price', 20, 8)->nullable()->after('trailing_stop_percentage')->comment('Highest price reached (for buy)');
            }
            if (!Schema::hasColumn('execution_positions', 'lowest_price')) {
                $table->decimal('lowest_price', 20, 8)->nullable()->after('highest_price')->comment('Lowest price reached (for sell)');
            }
            if (!Schema::hasColumn('execution_positions', 'breakeven_enabled')) {
                $table->boolean('breakeven_enabled')->default(false)->after('lowest_price');
            }
            if (!Schema::hasColumn('execution_positions', 'breakeven_trigger_price')) {
                $table->decimal('breakeven_trigger_price', 20, 8)->nullable()->after('breakeven_enabled')->comment('Price at which to move SL to breakeven');
            }
            if (!Schema::hasColumn('execution_positions', 'sl_moved_to_breakeven')) {
                $table->boolean('sl_moved_to_breakeven')->default(false)->after('breakeven_trigger_price');
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
        Schema::table('execution_positions', function (Blueprint $table) {
            $columns = [
                'trailing_stop_enabled',
                'trailing_stop_distance',
                'trailing_stop_percentage',
                'highest_price',
                'lowest_price',
                'breakeven_enabled',
                'breakeven_trigger_price',
                'sl_moved_to_breakeven',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('execution_positions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
