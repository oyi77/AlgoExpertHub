<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MakeSignalIdNullableInExecutionPositions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Get table prefix
        $prefix = Schema::getConnection()->getTablePrefix();
        $tableName = $prefix . 'execution_positions';
        
        // Check if table exists
        if (!Schema::hasTable('execution_positions')) {
            return;
        }
        
        // Check if signal_id column exists
        if (!Schema::hasColumn('execution_positions', 'signal_id')) {
            return;
        }
        
        Schema::table('execution_positions', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['signal_id']);
        });
        
        // Modify column to be nullable using raw SQL (more reliable)
        DB::statement("ALTER TABLE `{$tableName}` MODIFY `signal_id` BIGINT UNSIGNED NULL");
        
        Schema::table('execution_positions', function (Blueprint $table) {
            // Re-add foreign key with onDelete('set null') to handle nulls
            $table->foreign('signal_id')
                ->references('id')
                ->on('signals')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $prefix = Schema::getConnection()->getTablePrefix();
        $tableName = $prefix . 'execution_positions';
        
        if (!Schema::hasTable('execution_positions')) {
            return;
        }
        
        // First, set any null signal_id values to a default (or delete those rows)
        // For safety, we'll just drop the foreign key and make it NOT NULL
        Schema::table('execution_positions', function (Blueprint $table) {
            $table->dropForeign(['signal_id']);
        });
        
        // Set null values to 0 or delete rows with null signal_id
        DB::statement("UPDATE `{$tableName}` SET `signal_id` = 0 WHERE `signal_id` IS NULL");
        
        DB::statement("ALTER TABLE `{$tableName}` MODIFY `signal_id` BIGINT UNSIGNED NOT NULL");
        
        Schema::table('execution_positions', function (Blueprint $table) {
            $table->foreign('signal_id')
                ->references('id')
                ->on('signals')
                ->onDelete('cascade');
        });
    }
}

