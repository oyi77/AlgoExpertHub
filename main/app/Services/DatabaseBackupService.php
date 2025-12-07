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

            // Check if mysqldump command exists
            exec('which mysqldump 2>&1', $whichOutput, $whichReturn);
            if ($whichReturn !== 0) {
                return ['type' => 'error', 'message' => 'mysqldump command not found. Please install MySQL client tools.'];
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

            // Check if mysql command exists
            exec('which mysql 2>&1', $whichOutput, $whichReturn);
            if ($whichReturn !== 0) {
                return ['type' => 'error', 'message' => 'mysql command not found. Please install MySQL client tools.'];
            }

            $database = config('database.connections.' . config('database.default') . '.database');
            $username = config('database.connections.' . config('database.default') . '.username');
            $password = config('database.connections.' . config('database.default') . '.password');
            $host = config('database.connections.' . config('database.default') . '.host');
            $port = config('database.connections.' . config('database.default') . '.port', 3306);

            // Drop all tables first
            Artisan::call('db:wipe', ['--force' => true]);

            // Import backup
            if (!empty($password)) {
                $command = sprintf(
                    'mysql -h %s -P %s -u %s -p%s %s < %s 2>&1',
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($username),
                    escapeshellarg($password),
                    escapeshellarg($database),
                    escapeshellarg($filepath)
                );
            } else {
                $command = sprintf(
                    'mysql -h %s -P %s -u %s %s < %s 2>&1',
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
                \Log::error('Backup restore failed', ['command' => $command, 'output' => $output, 'return_var' => $returnVar]);
                return ['type' => 'error', 'message' => 'Restore failed: ' . $errorMsg];
            }

            // Clear caches
            Artisan::call('optimize:clear');

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

            // Check if mysql command exists
            exec('which mysql 2>&1', $whichOutput, $whichReturn);
            if ($whichReturn !== 0) {
                return ['type' => 'error', 'message' => 'mysql command not found. Please install MySQL client tools.'];
            }

            $database = config('database.connections.' . config('database.default') . '.database');
            $username = config('database.connections.' . config('database.default') . '.username');
            $password = config('database.connections.' . config('database.default') . '.password');
            $host = config('database.connections.' . config('database.default') . '.host');
            $port = config('database.connections.' . config('database.default') . '.port', 3306);

            // Drop all tables first
            Artisan::call('db:wipe', ['--force' => true]);

            // Import factory state
            if (!empty($password)) {
                $command = sprintf(
                    'mysql -h %s -P %s -u %s -p%s %s < %s 2>&1',
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($username),
                    escapeshellarg($password),
                    escapeshellarg($database),
                    escapeshellarg($this->factoryStatePath)
                );
            } else {
                $command = sprintf(
                    'mysql -h %s -P %s -u %s %s < %s 2>&1',
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($username),
                    escapeshellarg($database),
                    escapeshellarg($this->factoryStatePath)
                );
            }

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                $errorMsg = !empty($output) ? implode("\n", $output) : 'Unknown error';
                \Log::error('Factory state restore failed', ['command' => $command, 'output' => $output]);
                return ['type' => 'error', 'message' => 'Restore failed: ' . $errorMsg];
            }

            // Clear caches
            Artisan::call('optimize:clear');

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
}

