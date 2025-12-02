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
        // Only add foreign key if filter_strategies table exists
        if (Schema::hasTable('filter_strategies')) {
            Schema::table('trading_presets', function (Blueprint $table) {
                // Check if column already exists
                if (!Schema::hasColumn('trading_presets', 'filter_strategy_id')) {
                    // Filter Strategy reference (nullable for backward compatibility)
                    $table->unsignedBigInteger('filter_strategy_id')->nullable()->after('created_by_user_id');
                    $table->index('filter_strategy_id');
                    
                    // Add foreign key constraint
                    $table->foreign('filter_strategy_id')
                        ->references('id')
                        ->on('filter_strategies')
                        ->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trading_presets', function (Blueprint $table) {
            $table->dropForeign(['filter_strategy_id']);
            $table->dropIndex(['filter_strategy_id']);
            $table->dropColumn('filter_strategy_id');
        });
    }
};
