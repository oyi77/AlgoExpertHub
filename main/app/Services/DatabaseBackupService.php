<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DatabaseBackupService
{
    protected $backupPath;
    protected $factoryStatePath;

    public function __construct()
    {
        $this->backupPath = storage_path('app/database-backups');
        $this->factoryStatePath = database_path('sql/factory-state.sql');
        
        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    /**
     * Create database backup
     */
    public function createBackup(string $name = null): array
    {
        try {
            // Check if exec is available
            if (!function_exists('exec')) {
                return ['type' => 'error', 'message' => 'exec() function is not available. Please enable it in php.ini'];
            }

            // Check if mysqldump command exists (try Docker first, then host)
            $mysqlContainer = $this->getMysqlContainer();
            $useDocker = !empty($mysqlContainer);
            
            // Also check if we're in Docker and can use container hostname
            $host = config('database.connections.' . config('database.default') . '.host');
            $isDockerEnv = file_exists('/.dockerenv');
            
            if (!$useDocker) {
                // Try host mysqldump
                exec('which mysqldump 2>&1', $whichOutput, $whichReturn);
                if ($whichReturn !== 0) {
                    // If in Docker, try using MySQL container name as hostname (if mysql-client installed)
                    if ($isDockerEnv && !in_array($host, ['localhost', '127.0.0.1'])) {
                        // Host might already be container name, mysqldump might work if client is installed
                        // But we can't test without mysql-client, so provide helpful error
                        return [
                            'type' => 'error', 
                            'message' => 'mysqldump command not found in PHP container. To fix: Install mysql-client in the PHP container with: docker exec 1Panel-php8-mrTy apk add --no-cache mysql-client'
                        ];
                    }
                    return ['type' => 'error', 'message' => 'mysqldump command not found. Please install MySQL client tools.'];
                }
            }

            $name = $name ?? 'backup_' . date('Y-m-d_H-i-s');
            $filename = $this->sanitizeFilename($name) . '.sql';
            $filepath = $this->backupPath . '/' . $filename;

            // Ensure backup directory exists and is writable
            if (!File::exists($this->backupPath)) {
                File::makeDirectory($this->backupPath, 0755, true);
            }
            if (!is_writable($this->backupPath)) {
                return ['type' => 'error', 'message' => 'Backup directory is not writable: ' . $this->backupPath];
            }

            $database = config('database.connections.' . config('database.default') . '.database');
            $username = config('database.connections.' . config('database.default') . '.username');
            $password = config('database.connections.' . config('database.default') . '.password');
            $host = config('database.connections.' . config('database.default') . '.host');
            $port = config('database.connections.' . config('database.default') . '.port', 3306);

            // Build mysqldump command with proper password handling
            if (!empty($password)) {
                $command = sprintf(
                    'mysqldump -h %s -P %s -u %s -p%s %s --single-transaction --quick --lock-tables=false > %s 2>&1',
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($username),
                    escapeshellarg($password),
                    escapeshellarg($database),
                    escapeshellarg($filepath)
                );
            } else {
                $command = sprintf(
                    'mysqldump -h %s -P %s -u %s %s --single-transaction --quick --lock-tables=false > %s 2>&1',
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($username),
                    escapeshellarg($database),
                    escapeshellarg($filepath)
                );
            }

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                $errorMsg = !empty($output) ? implode("\n", $output) : 'Unknown error';
                // Clean up partial file if exists
                if (File::exists($filepath)) {
                    File::delete($filepath);
                }
                return ['type' => 'error', 'message' => 'Backup failed: ' . $errorMsg];
            }

            if (!File::exists($filepath)) {
                return ['type' => 'error', 'message' => 'Backup file was not created. Check file permissions.'];
            }

            // Check if file is not empty
            $size = File::size($filepath);
            if ($size === 0) {
                File::delete($filepath);
                return ['type' => 'error', 'message' => 'Backup file is empty. Please check database connection and credentials.'];
            }

            $tables = $this->countTables();

            return [
                'type' => 'success',
                'message' => 'Backup created successfully',
                'data' => [
                    'filename' => $filename,
                    'size' => $size,
                    'tables' => $tables,
                    'created_at' => now()->toDateTimeString()
                ]
            ];

        } catch (\Exception $e) {
            \Log::error('Backup creation error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['type' => 'error', 'message' => 'Backup error: ' . $e->getMessage()];
        }
    }

    /**
     * Load backup state
     */
    public function loadBackup(string $filename): array
    {
        try {
            // Check if exec is available
            if (!function_exists('exec')) {
                return ['type' => 'error', 'message' => 'exec() function is not available. Please enable it in php.ini'];
            }

            // Handle factory state file (stored in different location)
            if ($filename === 'factory-state.sql') {
                $filepath = $this->factoryStatePath;
            } else {
                $filepath = $this->backupPath . '/' . $filename;
            }

            if (!File::exists($filepath)) {
                return ['type' => 'error', 'message' => 'Backup file not found: ' . $filename];
            }

            // Check if we should use Docker
            $mysqlContainer = $this->getMysqlContainer();
            $useDocker = !empty($mysqlContainer);
            
            if (!$useDocker) {
                // Check if mysql command exists on host
                exec('which mysql 2>&1', $whichOutput, $whichReturn);
                if ($whichReturn !== 0) {
                    return ['type' => 'error', 'message' => 'mysql command not found. Please install MySQL client tools.'];
                }
            }

            $database = config('database.connections.' . config('database.default') . '.database');
            $username = config('database.connections.' . config('database.default') . '.username');
            $password = config('database.connections.' . config('database.default') . '.password');
            $host = config('database.connections.' . config('database.default') . '.host');
            $port = config('database.connections.' . config('database.default') . '.port', 3306);

            // Drop all tables first
            Artisan::call('db:wipe', ['--force' => true]);

            // Import backup
            if ($useDocker) {
                // Use Docker: copy file to container and import, or pipe from host
                // Since file is on host, we'll pipe it into docker exec
                if (!empty($password)) {
                    $command = sprintf(
                        'cat %s | docker exec -i %s mysql --ssl-mode=DISABLED -u %s -p%s %s 2>&1',
                        escapeshellarg($filepath),
                        escapeshellarg($mysqlContainer),
                        escapeshellarg($username),
                        escapeshellarg($password),
                        escapeshellarg($database)
                    );
                } else {
                    $command = sprintf(
                        'cat %s | docker exec -i %s mysql --ssl-mode=DISABLED -u %s %s 2>&1',
                        escapeshellarg($filepath),
                        escapeshellarg($mysqlContainer),
                        escapeshellarg($username),
                        escapeshellarg($database)
                    );
                }
            } else {
                // Use host mysql
                if (!empty($password)) {
                    $command = sprintf(
                        'mysql --ssl-mode=DISABLED -h %s -P %s -u %s -p%s %s < %s 2>&1',
                        escapeshellarg($host),
                        escapeshellarg($port),
                        escapeshellarg($username),
                        escapeshellarg($password),
                        escapeshellarg($database),
                        escapeshellarg($filepath)
                    );
                } else {
                    $command = sprintf(
                        'mysql --ssl-mode=DISABLED -h %s -P %s -u %s %s < %s 2>&1',
                        escapeshellarg($host),
                        escapeshellarg($port),
                        escapeshellarg($username),
                        escapeshellarg($database),
                        escapeshellarg($filepath)
                    );
                }
            }

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                $errorMsg = !empty($output) ? implode("\n", $output) : 'Unknown error';
                \Log::error('Backup restore failed', ['command' => $command, 'output' => $output, 'return_var' => $returnVar]);
                return ['type' => 'error', 'message' => 'Restore failed: ' . $errorMsg];
            }

            // Run migrations to ensure all tables exist (in case SQL is incomplete)
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Exception $e) {
                \Log::warning('Migration after restore failed (non-critical)', ['error' => $e->getMessage()]);
            }

            // Clear caches and refresh database connection
            Artisan::call('optimize:clear');
            
            // Refresh database connection to ensure it sees the restored tables
            try {
                DB::purge();
                DB::reconnect();
            } catch (\Exception $e) {
                \Log::warning('Could not refresh database connection', ['error' => $e->getMessage()]);
            }

            return ['type' => 'success', 'message' => 'Database restored from backup successfully'];

        } catch (\Exception $e) {
            \Log::error('Backup restore error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['type' => 'error', 'message' => 'Restore error: ' . $e->getMessage()];
        }
    }

    /**
     * Delete backup
     */
    public function deleteBackup(string $filename): array
    {
        try {
            $filepath = $this->backupPath . '/' . $filename;

            if (!File::exists($filepath)) {
                return ['type' => 'error', 'message' => 'Backup file not found'];
            }

            if ($filename === 'factory-state.sql') {
                return ['type' => 'error', 'message' => 'Cannot delete factory state'];
            }

            File::delete($filepath);

            return ['type' => 'success', 'message' => 'Backup deleted successfully'];

        } catch (\Exception $e) {
            return ['type' => 'error', 'message' => 'Delete error: ' . $e->getMessage()];
        }
    }

    /**
     * Save current state as factory default
     */
    public function saveAsFactoryState(string $sourceFilename = null): array
    {
        try {
            if ($sourceFilename) {
                // Copy from backup to factory state
                $sourcePath = $this->backupPath . '/' . $sourceFilename;
                if (!File::exists($sourcePath)) {
                    return ['type' => 'error', 'message' => 'Source backup not found'];
                }
                File::copy($sourcePath, $this->factoryStatePath);
            } else {
                // Create backup of current database as factory state
                $result = $this->exportToFile($this->factoryStatePath);
                if ($result['type'] === 'error') {
                    return $result;
                }
            }

            return ['type' => 'success', 'message' => 'Factory state saved successfully'];

        } catch (\Exception $e) {
            return ['type' => 'error', 'message' => 'Save error: ' . $e->getMessage()];
        }
    }

    /**
     * List all backups
     */
    public function listBackups(): array
    {
        $backups = [];
        
        try {
            // Ensure backup directory exists
            if (!File::exists($this->backupPath)) {
                File::makeDirectory($this->backupPath, 0755, true);
            }

            // Check if directory is readable
            if (!is_readable($this->backupPath)) {
                \Log::warning('Backup directory is not readable', ['path' => $this->backupPath]);
                return [];
            }

            $files = File::files($this->backupPath);
            
            foreach ($files as $file) {
                try {
                    if ($file->getExtension() === 'sql' && $file->getFilename() !== 'factory-state.sql') {
                        $backups[] = [
                            'filename' => $file->getFilename(),
                            'size' => $file->getSize(),
                            'size_human' => $this->formatBytes($file->getSize()),
                            'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
                            'is_factory' => false
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error reading backup file', ['file' => $file->getPathname(), 'error' => $e->getMessage()]);
                    continue;
                }
            }

            // Sort by creation date (newest first)
            usort($backups, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            // Add factory state if exists
            if (File::exists($this->factoryStatePath)) {
                try {
                    $factoryFile = new \SplFileInfo($this->factoryStatePath);
                    array_unshift($backups, [
                        'filename' => 'factory-state.sql',
                        'size' => $factoryFile->getSize(),
                        'size_human' => $this->formatBytes($factoryFile->getSize()),
                        'created_at' => date('Y-m-d H:i:s', $factoryFile->getMTime()),
                        'is_factory' => true
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Error reading factory state file', ['error' => $e->getMessage()]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error listing backups', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        return $backups;
    }

    /**
     * Load factory state
     */
    public function loadFactoryState(): array
    {
        if (!File::exists($this->factoryStatePath)) {
            // Factory state doesn't exist, run seeders instead
            try {
                Artisan::call('db:wipe', ['--force' => true]);
                Artisan::call('migrate', ['--force' => true]);
                Artisan::call('db:seed', ['--force' => true]);
                Artisan::call('optimize:clear');

                return ['type' => 'success', 'message' => 'Database reset to factory state via seeders'];
            } catch (\Exception $e) {
                \Log::error('Factory state restore error', ['error' => $e->getMessage()]);
                return ['type' => 'error', 'message' => 'Failed to restore factory state: ' . $e->getMessage()];
            }
        }

        // Load factory state from its actual location
        try {
            if (!function_exists('exec')) {
                return ['type' => 'error', 'message' => 'exec() function is not available. Please enable it in php.ini'];
            }

            // Check if we should use Docker
            $mysqlContainer = $this->getMysqlContainer();
            $useDocker = !empty($mysqlContainer);
            
            if (!$useDocker) {
                // Check if mysql command exists on host
                exec('which mysql 2>&1', $whichOutput, $whichReturn);
                if ($whichReturn !== 0) {
                    return ['type' => 'error', 'message' => 'mysql command not found. Please install MySQL client tools.'];
                }
            }

            $database = config('database.connections.' . config('database.default') . '.database');
            $username = config('database.connections.' . config('database.default') . '.username');
            $password = config('database.connections.' . config('database.default') . '.password');
            $host = config('database.connections.' . config('database.default') . '.host');
            $port = config('database.connections.' . config('database.default') . '.port', 3306);

            // Drop all tables first
            Artisan::call('db:wipe', ['--force' => true]);

            // Import factory state
            if ($useDocker) {
                // Use Docker: pipe file into container
                if (!empty($password)) {
                    $command = sprintf(
                        'cat %s | docker exec -i %s mysql --ssl-mode=DISABLED -u %s -p%s %s 2>&1',
                        escapeshellarg($this->factoryStatePath),
                        escapeshellarg($mysqlContainer),
                        escapeshellarg($username),
                        escapeshellarg($password),
                        escapeshellarg($database)
                    );
                } else {
                    $command = sprintf(
                        'cat %s | docker exec -i %s mysql --ssl-mode=DISABLED -u %s %s 2>&1',
                        escapeshellarg($this->factoryStatePath),
                        escapeshellarg($mysqlContainer),
                        escapeshellarg($username),
                        escapeshellarg($database)
                    );
                }
            } else {
                // Use host mysql
                if (!empty($password)) {
                    $command = sprintf(
                        'mysql --ssl-mode=DISABLED -h %s -P %s -u %s -p%s %s < %s 2>&1',
                        escapeshellarg($host),
                        escapeshellarg($port),
                        escapeshellarg($username),
                        escapeshellarg($password),
                        escapeshellarg($database),
                        escapeshellarg($this->factoryStatePath)
                    );
                } else {
                    $command = sprintf(
                        'mysql --ssl-mode=DISABLED -h %s -P %s -u %s %s < %s 2>&1',
                        escapeshellarg($host),
                        escapeshellarg($port),
                        escapeshellarg($username),
                        escapeshellarg($database),
                        escapeshellarg($this->factoryStatePath)
                    );
                }
            }

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                $errorMsg = !empty($output) ? implode("\n", $output) : 'Unknown error';
                \Log::error('Factory state restore failed', ['command' => $command, 'output' => $output, 'return_var' => $returnVar]);
                return ['type' => 'error', 'message' => 'Restore failed: ' . $errorMsg];
            }

            // Log restore completion
            \Log::info('Factory state SQL import completed', ['output_lines' => count($output), 'return_var' => $returnVar]);
            
            // Verify data was imported (check for admin record)
            try {
                $adminCount = DB::table('admins')->count();
                if ($adminCount === 0) {
                    \Log::warning('Factory restore completed but admin table is empty. SQL import may have failed partially.');
                    // Try to re-import just the data (skip CREATE TABLE statements)
                    // This is a fallback - ideally the full import should work
                } else {
                    \Log::info('Factory restore verified: admin records found', ['count' => $adminCount]);
                }
            } catch (\Exception $e) {
                \Log::warning('Could not verify admin data after restore', ['error' => $e->getMessage()]);
            }

            // Run migrations to ensure all tables exist (in case SQL is incomplete or outdated)
            try {
                \Log::info('Running migrations after factory restore');
                Artisan::call('migrate', ['--force' => true]);
                \Log::info('Migrations completed after factory restore');
            } catch (\Exception $e) {
                \Log::error('Migration after factory restore failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                // Don't fail the restore if migrations fail, but log it
            }

            // Verify critical tables exist
            try {
                $criticalTables = ['admins', 'users', 'plans', 'signals'];
                $missingTables = [];
                foreach ($criticalTables as $table) {
                    if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                        $missingTables[] = $table;
                    }
                }
                if (!empty($missingTables)) {
                    \Log::warning('Critical tables missing after factory restore', ['missing' => $missingTables]);
                    // Try to run migrations again, but only for missing tables
                    try {
                        // Run fresh migrations if critical tables are missing
                        if (in_array('admins', $missingTables)) {
                            \Log::info('Running fresh migrations to create missing tables');
                            Artisan::call('migrate:fresh', ['--force' => true, '--seed' => false]);
                            // Re-import factory state data after fresh migration
                            if ($useDocker) {
                                if (!empty($password)) {
                                    $reimportCommand = sprintf(
                                        'cat %s | docker exec -i %s mysql --ssl-mode=DISABLED -u %s -p%s %s 2>&1',
                                        escapeshellarg($this->factoryStatePath),
                                        escapeshellarg($mysqlContainer),
                                        escapeshellarg($username),
                                        escapeshellarg($password),
                                        escapeshellarg($database)
                                    );
                                } else {
                                    $reimportCommand = sprintf(
                                        'cat %s | docker exec -i %s mysql --ssl-mode=DISABLED -u %s %s 2>&1',
                                        escapeshellarg($this->factoryStatePath),
                                        escapeshellarg($mysqlContainer),
                                        escapeshellarg($username),
                                        escapeshellarg($database)
                                    );
                                }
                                exec($reimportCommand, $reimportOutput, $reimportReturn);
                                if ($reimportReturn !== 0) {
                                    \Log::error('Re-import after fresh migration failed', ['output' => $reimportOutput]);
                                }
                            }
                        } else {
                            // Just run migrations for missing tables
                            Artisan::call('migrate', ['--force' => true]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Second migration attempt failed', ['error' => $e->getMessage()]);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Could not verify tables after restore', ['error' => $e->getMessage()]);
            }

            // Clear caches and refresh database connection
            Artisan::call('optimize:clear');
            
            // Refresh database connection to ensure it sees the restored tables
            try {
                DB::purge();
                DB::reconnect();
            } catch (\Exception $e) {
                \Log::warning('Could not refresh database connection', ['error' => $e->getMessage()]);
            }

            return ['type' => 'success', 'message' => 'Database restored to factory state successfully'];
        } catch (\Exception $e) {
            \Log::error('Factory state restore error', ['error' => $e->getMessage()]);
            return ['type' => 'error', 'message' => 'Restore error: ' . $e->getMessage()];
        }
    }

    /**
     * Export current database to file
     */
    protected function exportToFile(string $filepath): array
    {
        try {
            // Ensure directory exists
            $dir = dirname($filepath);
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $database = config('database.connections.' . config('database.default') . '.database');
            $username = config('database.connections.' . config('database.default') . '.username');
            $password = config('database.connections.' . config('database.default') . '.password');
            $host = config('database.connections.' . config('database.default') . '.host');
            $port = config('database.connections.' . config('database.default') . '.port', 3306);

            // Check if we should use Docker
            $mysqlContainer = $this->getMysqlContainer();
            $useDocker = !empty($mysqlContainer);

            // Build mysqldump command with proper password handling
            if ($useDocker) {
                // Use Docker: run mysqldump in MySQL container and pipe output to host file
                if (!empty($password)) {
                    $command = sprintf(
                        'docker exec %s mysqldump -u %s -p%s %s --single-transaction --quick --lock-tables=false 2>&1 > %s',
                        escapeshellarg($mysqlContainer),
                        escapeshellarg($username),
                        escapeshellarg($password),
                        escapeshellarg($database),
                        escapeshellarg($filepath)
                    );
                } else {
                    $command = sprintf(
                        'docker exec %s mysqldump -u %s %s --single-transaction --quick --lock-tables=false 2>&1 > %s',
                        escapeshellarg($mysqlContainer),
                        escapeshellarg($username),
                        escapeshellarg($database),
                        escapeshellarg($filepath)
                    );
                }
            } else {
                // Use host mysqldump
                if (!empty($password)) {
                    $command = sprintf(
                        'mysqldump -h %s -P %s -u %s -p%s %s --single-transaction --quick --lock-tables=false > %s 2>&1',
                        escapeshellarg($host),
                        escapeshellarg($port),
                        escapeshellarg($username),
                        escapeshellarg($password),
                        escapeshellarg($database),
                        escapeshellarg($filepath)
                    );
                } else {
                    $command = sprintf(
                        'mysqldump -h %s -P %s -u %s %s --single-transaction --quick --lock-tables=false > %s 2>&1',
                        escapeshellarg($host),
                        escapeshellarg($port),
                        escapeshellarg($username),
                        escapeshellarg($database),
                        escapeshellarg($filepath)
                    );
                }
            }

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                $errorMsg = !empty($output) ? implode("\n", $output) : 'Unknown error';
                return ['type' => 'error', 'message' => 'Export failed: ' . $errorMsg];
            }

            if (!File::exists($filepath) || File::size($filepath) === 0) {
                return ['type' => 'error', 'message' => 'Export file is empty or was not created'];
            }

            return ['type' => 'success', 'message' => 'Exported successfully'];

        } catch (\Exception $e) {
            \Log::error('Export to file error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['type' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Count database tables
     */
    protected function countTables(): int
    {
        $database = config('database.connections.' . config('database.default') . '.database');
        $tables = DB::select('SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?', [$database]);
        return $tables[0]->count ?? 0;
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Sanitize filename
     */
    protected function sanitizeFilename(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    }

    /**
     * Get MySQL Docker container name
     */
    protected function getMysqlContainer(): ?string
    {
        // Common 1Panel MySQL container name patterns (try most specific first)
        $possibleContainers = [
            '1Panel-mysql-L7KM',  // Current detected container name
            '1panel-mysql-L7KM',
        ];

        // First, try to find MySQL container using docker command (if available)
        exec('which docker 2>&1', $dockerOutput, $dockerReturn);
        if ($dockerReturn === 0) {
            // Try to find MySQL container from host
            exec('docker ps --format "{{.Names}}" | grep -i mysql 2>&1', $containers, $return);
            if ($return === 0 && !empty($containers)) {
                $container = trim($containers[0]);
                if (!empty($container)) {
                    // Verify container has mysqldump
                    exec("docker exec {$container} mysqldump --version 2>&1", $verifyOutput, $verifyReturn);
                    if ($verifyReturn === 0) {
                        return $container;
                    }
                }
            }
        }

        // Fallback: Try common container names directly
        // This works even if docker command isn't in PATH but docker socket is accessible
        foreach ($possibleContainers as $containerName) {
            // Test if container exists and has mysqldump by trying to get version
            exec("docker exec {$containerName} mysqldump --version 2>&1", $testOutput, $testReturn);
            if ($testReturn === 0) {
                return $containerName;
            }
        }

        // Last resort: Try generic names
        $genericNames = ['mysql', '1panel-mysql', '1Panel-mysql'];
        foreach ($genericNames as $containerName) {
            exec("docker exec {$containerName} mysqldump --version 2>&1", $testOutput, $testReturn);
            if ($testReturn === 0) {
                return $containerName;
            }
        }

        return null;
    }
}

