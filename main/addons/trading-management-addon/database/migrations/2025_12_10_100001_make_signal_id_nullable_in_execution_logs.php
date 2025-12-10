<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MakeSignalIdNullableInExecutionLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Get table prefix from connection
        $prefix = Schema::getConnection()->getTablePrefix();
        $tableName = $prefix . 'execution_logs';
        
        // Check if table exists (with prefix)
        if (!Schema::hasTable('execution_logs')) {
            // Try with prefix explicitly
            try {
                $exists = DB::select("SHOW TABLES LIKE '{$tableName}'");
                if (empty($exists)) {
                    \Log::warning("Table {$tableName} does not exist");
                    return;
                }
            } catch (\Exception $e) {
                \Log::warning("Could not check if table exists: " . $e->getMessage());
                return;
            }
        }

        // Check if foreign key exists before dropping
        try {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND COLUMN_NAME = 'signal_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$tableName]);
            
            if (!empty($foreignKeys)) {
                $fkName = $foreignKeys[0]->CONSTRAINT_NAME;
                Schema::table('execution_logs', function (Blueprint $table) use ($fkName) {
                    $table->dropForeign([$fkName]);
                });
            }
        } catch (\Exception $e) {
            // If query fails, try to drop by column name
            try {
                Schema::table('execution_logs', function (Blueprint $table) {
                    $table->dropForeign(['signal_id']);
                });
            } catch (\Exception $e2) {
                \Log::warning('Could not drop foreign key for signal_id: ' . $e2->getMessage());
            }
        }

        // Modify column to nullable (use table name with prefix)
        DB::statement("ALTER TABLE `{$tableName}` MODIFY `signal_id` BIGINT UNSIGNED NULL");

        // Re-add foreign key constraint with nullable support (only if signals table exists)
        if (Schema::hasTable('signals')) {
            try {
                // Use DB::statement to add foreign key with prefix
                $signalsTable = $prefix . 'signals';
                DB::statement("
                    ALTER TABLE `{$tableName}` 
                    ADD CONSTRAINT `execution_logs_signal_id_foreign` 
                    FOREIGN KEY (`signal_id`) 
                    REFERENCES `{$signalsTable}` (`id`) 
                    ON DELETE CASCADE
                ");
            } catch (\Exception $e) {
                \Log::warning('Could not add foreign key for signal_id: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Get table prefix from connection
        $prefix = Schema::getConnection()->getTablePrefix();
        $tableName = $prefix . 'execution_logs';
        $signalsTable = $prefix . 'signals';
        
        if (!Schema::hasTable('execution_logs')) {
            return;
        }

        // First, set all NULL signal_id to a default value (or delete those records)
        // For safety, we'll set them to 1 (which should exist) or delete them
        DB::statement("UPDATE `{$tableName}` SET `signal_id` = 1 WHERE `signal_id` IS NULL");

        Schema::table('execution_logs', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['signal_id']);
        });

        // Make column NOT NULL again
        DB::statement("ALTER TABLE `{$tableName}` MODIFY `signal_id` BIGINT UNSIGNED NOT NULL");

        // Re-add foreign key using DB::statement with prefix
        $signalsTable = $prefix . 'signals';
        DB::statement("
            ALTER TABLE `{$tableName}` 
            ADD CONSTRAINT `execution_logs_signal_id_foreign` 
            FOREIGN KEY (`signal_id`) 
            REFERENCES `{$signalsTable}` (`id`) 
            ON DELETE CASCADE
        ");
    }
}

