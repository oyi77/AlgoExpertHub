<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Allow null exchange_connection_id for templates
 * 
 * Templates don't need connections - users provide them during clone
 * Uses raw SQL to avoid requiring Doctrine DBAL
 */
class AllowNullExchangeConnectionForTemplates extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('trading_bots')) {
            return;
        }

        $tablePrefix = DB::getTablePrefix();
        $tableNameWithoutPrefix = 'trading_bots';
        $tableName = $tablePrefix . $tableNameWithoutPrefix;
        $columnName = 'exchange_connection_id';
        $databaseName = DB::connection()->getDatabaseName();
        
        // Check if column exists and is NOT NULL (check both with and without prefix)
        $columns = DB::select("
            SELECT IS_NULLABLE, COLUMN_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = ? 
            AND COLUMN_NAME = ?
        ", [$databaseName, $tableName, $columnName]);
        
        // If not found with prefix, try without
        if (empty($columns)) {
            $columns = DB::select("
                SELECT IS_NULLABLE, COLUMN_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = ? 
                AND COLUMN_NAME = ?
            ", [$databaseName, $tableNameWithoutPrefix, $columnName]);
            if (!empty($columns)) {
                $tableName = $tableNameWithoutPrefix;
            }
        }
        
        if (!empty($columns) && $columns[0]->IS_NULLABLE === 'NO') {
            // Find and drop foreign key
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = ? 
                AND COLUMN_NAME = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$databaseName, $tableName, $columnName]);
            
            if (!empty($foreignKeys)) {
                foreach ($foreignKeys as $fk) {
                    try {
                        DB::statement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    } catch (\Exception $e) {
                        // Continue if FK doesn't exist
                    }
                }
            }
            
            // Modify column to allow NULL using raw SQL
            DB::statement("ALTER TABLE `{$tableName}` MODIFY COLUMN `{$columnName}` BIGINT UNSIGNED NULL");
            
            // Re-add foreign key if table exists
            $refTable = null;
            if (Schema::hasTable('exchange_connections')) {
                $refTable = $tablePrefix . 'exchange_connections';
            } elseif (Schema::hasTable('execution_connections')) {
                $refTable = $tablePrefix . 'execution_connections';
            }
            
            if ($refTable) {
                try {
                    $fkName = "{$tableName}_{$columnName}_foreign";
                    DB::statement("
                        ALTER TABLE `{$tableName}` 
                        ADD CONSTRAINT `{$fkName}` 
                        FOREIGN KEY (`{$columnName}`) 
                        REFERENCES `{$refTable}`(`id`) 
                        ON DELETE CASCADE
                    ");
                } catch (\Exception $e) {
                    // FK might already exist or constraint name conflict
                    // Use auto-generated name
                    try {
                        DB::statement("
                            ALTER TABLE `{$tableName}` 
                            ADD FOREIGN KEY (`{$columnName}`) 
                            REFERENCES `{$refTable}`(`id`) 
                            ON DELETE CASCADE
                        ");
                    } catch (\Exception $e2) {
                        // Skip if still fails
                    }
                }
            }
        }
    }

    public function down()
    {
        // Note: Not reverting to NOT NULL to preserve data safety
        // This migration is one-way for safety
    }
}
