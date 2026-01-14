<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FillEmptyTablesFromSource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:fill-from-source 
                            {--source-db=algotrad-signals : Source database name}
                            {--source-host= : Source database host (defaults to current)}
                            {--source-user= : Source database user (defaults to current)}
                            {--source-password= : Source database password (defaults to current)}
                            {--dry-run : Show what would be imported without actually importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for empty tables and fill them with data from source database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sourceDb = $this->option('source-db');
        $dryRun = $this->option('dry-run');
        
        $this->info("Checking for empty tables and importing from database: {$sourceDb}");
        
        // Get current database configuration
        $currentConfig = config('database.connections.' . config('database.default'));
        $currentDb = $currentConfig['database'];
        $host = $this->option('source-host') ?: $currentConfig['host'];
        $user = $this->option('source-user') ?: $currentConfig['username'];
        $password = $this->option('source-password') ?: $currentConfig['password'];
        $port = $currentConfig['port'] ?? 3306;
        $prefix = $currentConfig['prefix'] ?? '';
        
        $this->info("Current database: {$currentDb}");
        $this->info("Source database: {$sourceDb}");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No data will be imported");
        }
        
        // Get all tables from current database
        $tables = $this->getTables($currentDb, $prefix);
        
        $this->info("\nFound " . count($tables) . " tables in current database");
        
        // Connect to source database
        try {
            $sourceConnection = $this->createSourceConnection($sourceDb, $host, $user, $password, $port);
            $sourceTables = $this->getTablesFromSource($sourceConnection, $sourceDb);
            
            $this->info("Found " . count($sourceTables) . " tables in source database");
        } catch (\Exception $e) {
            $this->error("Failed to connect to source database: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        $emptyTables = [];
        $imported = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($tables as $baseTableName => $fullTableName) {
            // $baseTableName is without prefix, $fullTableName is with prefix
            
            // Check if table is empty
            // Use raw SQL to bypass Laravel's prefix handling since we already have the full name
            try {
                $result = DB::selectOne("SELECT COUNT(*) as count FROM `{$fullTableName}`");
                $count = (int) $result->count;
            } catch (\Exception $e) {
                $this->warn("Error checking table '{$baseTableName}' ({$fullTableName}): " . $e->getMessage());
                continue;
            }
            
            if ($count == 0) {
                $this->info("Table '{$baseTableName}' is empty (count: {$count})");
                $emptyTables[] = $baseTableName;
                
                // Check if source table exists (try with and without prefix)
                $sourceTableName = $this->findSourceTable($sourceConnection, $baseTableName, $prefix);
                
                if ($sourceTableName) {
                    $sourceCount = $this->getSourceTableCount($sourceConnection, $sourceTableName);
                    
                    if ($sourceCount > 0) {
                        $this->info("  → Found source table '{$sourceTableName}' with {$sourceCount} records");
                        
                        if (!$dryRun) {
                            try {
                                $importedCount = $this->importTableData(
                                    $sourceConnection,
                                    $sourceTableName,
                                    $fullTableName,
                                    $baseTableName
                                );
                                $imported += $importedCount;
                                $this->info("  ✓ Imported {$importedCount} records into '{$baseTableName}'");
                            } catch (\Exception $e) {
                                $this->error("  ✗ Failed to import '{$baseTableName}': " . $e->getMessage());
                                $errors++;
                            }
                        } else {
                            $this->info("  → Would import {$sourceCount} records into '{$baseTableName}' (DRY RUN)");
                            $imported++;
                        }
                    } else {
                        $this->warn("  → Source table '{$sourceTableName}' is empty, skipping");
                        $skipped++;
                    }
                } else {
                    $this->warn("  → Source table not found for '{$baseTableName}', skipping");
                    $skipped++;
                }
            } else {
                $this->line("Table '{$baseTableName}' has {$count} records, skipping");
            }
        }
        
        $this->newLine();
        $this->info("=== Summary ===");
        $this->info("Empty tables found: " . count($emptyTables));
        $this->info("Tables imported: {$imported}");
        $this->info("Tables skipped: {$skipped}");
        $this->info("Errors: {$errors}");
        
        if ($dryRun) {
            $this->warn("\nThis was a DRY RUN. Run without --dry-run to actually import data.");
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Get all tables from current database
     */
    private function getTables(string $database, string $prefix): array
    {
        $tables = DB::select("SHOW TABLES");
        
        $tableList = [];
        $key = "Tables_in_{$database}";
        
        foreach ($tables as $table) {
            $tableName = $table->$key;
            
            // Store full table name (with prefix) for later use
            // But also store base name for matching
            if ($prefix && str_starts_with($tableName, $prefix)) {
                $baseName = substr($tableName, strlen($prefix));
                $tableList[$baseName] = $tableName; // base_name => full_table_name
            } else {
                $tableList[$tableName] = $tableName;
            }
        }
        
        return $tableList;
    }
    
    /**
     * Create connection to source database
     */
    private function createSourceConnection(string $database, string $host, string $user, string $password, int $port): \PDO
    {
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        
        return new \PDO($dsn, $user, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
        ]);
    }
    
    /**
     * Get all tables from source database
     */
    private function getTablesFromSource(\PDO $connection, string $database): array
    {
        $stmt = $connection->query("SHOW TABLES");
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        return $tables;
    }
    
    /**
     * Find matching source table (try with and without prefix)
     */
    private function findSourceTable(\PDO $connection, string $table, string $prefix): ?string
    {
        $sourceTables = $this->getTablesFromSource($connection, '');
        
        // Try exact match first
        if (in_array($table, $sourceTables)) {
            return $table;
        }
        
        // Try with prefix
        $prefixedTable = $prefix . $table;
        if (in_array($prefixedTable, $sourceTables)) {
            return $prefixedTable;
        }
        
        // Try without prefix from source
        foreach ($sourceTables as $sourceTable) {
            if ($prefix && str_starts_with($sourceTable, $prefix)) {
                $unprefixed = substr($sourceTable, strlen($prefix));
                if ($unprefixed === $table) {
                    return $sourceTable;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get count from source table
     */
    private function getSourceTableCount(\PDO $connection, string $table): int
    {
        $stmt = $connection->query("SELECT COUNT(*) as count FROM `{$table}`");
        $result = $stmt->fetch();
        
        return (int) $result->count;
    }
    
    /**
     * Import data from source table to target table
     */
    private function importTableData(\PDO $sourceConnection, string $sourceTable, string $targetTable, string $tableName): int
    {
        // Get source data
        $stmt = $sourceConnection->query("SELECT * FROM `{$sourceTable}`");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            return 0;
        }
        
        // Get column names
        $columns = array_keys($rows[0]);
        
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Insert data in chunks
        $chunkSize = 100;
        $imported = 0;
        
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            try {
                // Build insert query using raw SQL to bypass prefix handling
                $placeholders = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
                $allPlaceholders = implode(',', array_fill(0, count($chunk), $placeholders));
                
                $columnList = '`' . implode('`,`', $columns) . '`';
                
                $sql = "INSERT INTO `{$targetTable}` ({$columnList}) VALUES {$allPlaceholders}";
                
                $values = [];
                foreach ($chunk as $row) {
                    foreach ($columns as $col) {
                        $values[] = $row[$col] ?? null;
                    }
                }
                
                DB::statement($sql, $values);
                $imported += count($chunk);
            } catch (\Exception $e) {
                // If bulk insert fails, try one by one
                foreach ($chunk as $row) {
                    try {
                        // Use raw SQL for individual inserts too
                        $placeholders = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
                        $columnList = '`' . implode('`,`', $columns) . '`';
                        $sql = "INSERT INTO `{$targetTable}` ({$columnList}) VALUES {$placeholders}";
                        $values = [];
                        foreach ($columns as $col) {
                            $values[] = $row[$col] ?? null;
                        }
                        DB::statement($sql, $values);
                        $imported++;
                    } catch (\Exception $e2) {
                        // Skip problematic rows
                        continue;
                    }
                }
            }
        }
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        return $imported;
    }
}
