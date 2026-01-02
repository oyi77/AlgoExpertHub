<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Fix data consistency issues in trading_bots and related tables
 * 
 * Adds missing indexes and ensures foreign key constraints are properly set
 */
return new class extends Migration
{
    public function up()
    {
        // Fix trading_bots table
        if (Schema::hasTable('trading_bots')) {
            Schema::table('trading_bots', function (Blueprint $table) {
                // Add missing indexes for frequently queried columns
                if (!$this->hasIndex('trading_bots', 'filter_strategy_id')) {
                    $table->index('filter_strategy_id');
                }
                if (!$this->hasIndex('trading_bots', 'ai_model_profile_id')) {
                    $table->index('ai_model_profile_id');
                }
                if (!$this->hasIndex('trading_bots', 'is_paper_trading')) {
                    $table->index('is_paper_trading');
                }
                // Composite index for common queries
                if (!$this->hasIndex('trading_bots', ['user_id', 'is_active'])) {
                    $table->index(['user_id', 'is_active']);
                }
            });
        }

        // Fix execution_logs table - ensure signal_id foreign key exists
        if (Schema::hasTable('execution_logs')) {
            $prefix = Schema::getConnection()->getTablePrefix();
            $tableName = $prefix . 'execution_logs';
            
            // Check if foreign key exists
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND COLUMN_NAME = 'signal_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$tableName]);
            
            // Add foreign key if it doesn't exist and signals table exists
            if (empty($foreignKeys) && Schema::hasTable('signals')) {
                try {
                    $signalsTable = $prefix . 'signals';
                    DB::statement("
                        ALTER TABLE `{$tableName}` 
                        ADD CONSTRAINT `execution_logs_signal_id_foreign` 
                        FOREIGN KEY (`signal_id`) 
                        REFERENCES `{$signalsTable}` (`id`) 
                        ON DELETE CASCADE
                    ");
                } catch (\Exception $e) {
                    \Log::warning('Could not add foreign key for execution_logs.signal_id: ' . $e->getMessage());
                }
            }
        }

        // Fix execution_positions table - ensure signal_id foreign key exists
        if (Schema::hasTable('execution_positions')) {
            $prefix = Schema::getConnection()->getTablePrefix();
            $tableName = $prefix . 'execution_positions';
            
            // Check if foreign key exists
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND COLUMN_NAME = 'signal_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$tableName]);
            
            // Add foreign key if it doesn't exist and signals table exists
            if (empty($foreignKeys) && Schema::hasTable('signals')) {
                try {
                    $signalsTable = $prefix . 'signals';
                    DB::statement("
                        ALTER TABLE `{$tableName}` 
                        ADD CONSTRAINT `execution_positions_signal_id_foreign` 
                        FOREIGN KEY (`signal_id`) 
                        REFERENCES `{$signalsTable}` (`id`) 
                        ON DELETE SET NULL
                    ");
                } catch (\Exception $e) {
                    \Log::warning('Could not add foreign key for execution_positions.signal_id: ' . $e->getMessage());
                }
            }
        }
    }

    public function down()
    {
        // Remove indexes (foreign keys should remain)
        if (Schema::hasTable('trading_bots')) {
            Schema::table('trading_bots', function (Blueprint $table) {
                $table->dropIndex(['filter_strategy_id']);
                $table->dropIndex(['ai_model_profile_id']);
                $table->dropIndex(['is_paper_trading']);
                $table->dropIndex(['user_id', 'is_active']);
            });
        }
    }

    /**
     * Check if index exists on table
     */
    protected function hasIndex(string $table, $columns): bool
    {
        try {
            $prefix = Schema::getConnection()->getTablePrefix();
            $tableName = $prefix . $table;
            
            $columnsArray = is_array($columns) ? $columns : [$columns];
            $columnList = implode(',', array_map(fn($col) => "`{$col}`", $columnsArray));
            
            $indexes = DB::select("
                SELECT INDEX_NAME 
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND COLUMN_NAME IN (" . implode(',', array_fill(0, count($columnsArray), '?')) . ")
                GROUP BY INDEX_NAME
                HAVING COUNT(DISTINCT COLUMN_NAME) = ?
            ", array_merge([$tableName], $columnsArray, [count($columnsArray)]));
            
            return !empty($indexes);
        } catch (\Exception $e) {
            // If check fails, assume index doesn't exist to be safe
            return false;
        }
    }
};
