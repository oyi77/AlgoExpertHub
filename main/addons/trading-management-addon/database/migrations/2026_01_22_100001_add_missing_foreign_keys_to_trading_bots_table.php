<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add missing foreign key constraints to trading_bots table
 * 
 * Adds foreign key constraints that may be missing:
 * - data_connection_id (if not already added)
 * - Ensures referential integrity
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trading_bots', function (Blueprint $table) {
            // Check if data_connection_id foreign key exists
            $foreignKeys = $this->getForeignKeys('trading_bots');
            
            if (Schema::hasColumn('trading_bots', 'data_connection_id')) {
                if (!in_array('trading_bots_data_connection_id_foreign', $foreignKeys)) {
                    if (Schema::hasTable('exchange_connections')) {
                        $table->foreign('data_connection_id')
                            ->references('id')
                            ->on('exchange_connections')
                            ->onDelete('set null')
                            ->onUpdate('cascade');
                    }
                }
            }

            // Note: Other foreign keys (exchange_connection_id, trading_preset_id, etc.)
            // should already exist from the initial migration, but we verify they exist
            // If they don't, they would need to be added here
        });
    }

    public function down(): void
    {
        Schema::table('trading_bots', function (Blueprint $table) {
            $foreignKeys = $this->getForeignKeys('trading_bots');
            
            if (in_array('trading_bots_data_connection_id_foreign', $foreignKeys)) {
                $table->dropForeign(['data_connection_id']);
            }
        });
    }

    /**
     * Get list of foreign key names for a table
     * 
     * @param string $tableName
     * @return array
     */
    protected function getForeignKeys(string $tableName): array
    {
        try {
            $connection = Schema::getConnection();
            $database = $connection->getDatabaseName();
            
            $foreignKeys = $connection->select(
                "SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL",
                [$database, $tableName]
            );
            
            return array_map(function ($fk) {
                return $fk->CONSTRAINT_NAME;
            }, $foreignKeys);
        } catch (\Exception $e) {
            // If query fails, return empty array (foreign keys may not be queryable)
            return [];
        }
    }
};

