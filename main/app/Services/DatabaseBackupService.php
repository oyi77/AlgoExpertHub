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
            $name = $name ?? 'backup_' . date('Y-m-d_H-i-s');
            $filename = $this->sanitizeFilename($name) . '.sql';
            $filepath = $this->backupPath . '/' . $filename;

            $database = config('database.connections.' . config('database.default') . '.database');
            $username = config('database.connections.' . config('database.default') . '.username');
            $password = config('database.connections.' . config('database.default') . '.password');
            $host = config('database.connections.' . config('database.default') . '.host');

            $command = sprintf(
                'mysqldump -h %s -u %s -p%s %s > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0 || !File::exists($filepath)) {
                return ['type' => 'error', 'message' => 'Backup failed: ' . implode("\n", $output)];
            }

            $size = File::size($filepath);
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
            return ['type' => 'error', 'message' => 'Backup error: ' . $e->getMessage()];
        }
    }

    /**
     * Load backup state
     */
    public function loadBackup(string $filename): array
    {
        try {
            $filepath = $this->backupPath . '/' . $filename;

            if (!File::exists($filepath)) {
                return ['type' => 'error', 'message' => 'Backup file not found'];
            }

            $database = config('database.connections.' . config('database.default') . '.database');
            $username = config('database.connections.' . config('database.default') . '.username');
            $password = config('database.connections.' . config('database.default') . '.password');
            $host = config('database.connections.' . config('database.default') . '.host');

            // Drop all tables first
            Artisan::call('db:wipe', ['--force' => true]);

            // Import backup
            $command = sprintf(
                'mysql -h %s -u %s -p%s %s < %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                return ['type' => 'error', 'message' => 'Restore failed: ' . implode("\n", $output)];
            }

            // Clear caches
            Artisan::call('optimize:clear');

            return ['type' => 'success', 'message' => 'Database restored from backup successfully'];

        } catch (\Exception $e) {
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
        
        $files = File::files($this->backupPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'sql') {
                $backups[] = [
                    'filename' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'size_human' => $this->formatBytes($file->getSize()),
                    'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
                    'is_factory' => false
                ];
            }
        }

        // Add factory state if exists
        if (File::exists($this->factoryStatePath)) {
            $factoryFile = new \SplFileInfo($this->factoryStatePath);
            array_unshift($backups, [
                'filename' => 'factory-state.sql',
                'size' => $factoryFile->getSize(),
                'size_human' => $this->formatBytes($factoryFile->getSize()),
                'created_at' => date('Y-m-d H:i:s', $factoryFile->getMTime()),
                'is_factory' => true
            ]);
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
            Artisan::call('db:wipe', ['--force' => true]);
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);
            Artisan::call('optimize:clear');

            return ['type' => 'success', 'message' => 'Database reset to factory state via seeders'];
        }

        return $this->loadBackup('factory-state.sql');
    }

    /**
     * Export current database to file
     */
    protected function exportToFile(string $filepath): array
    {
        try {
            $database = config('database.connections.' . config('database.default') . '.database');
            $username = config('database.connections.' . config('database.default') . '.username');
            $password = config('database.connections.' . config('database.default') . '.password');
            $host = config('database.connections.' . config('database.default') . '.host');

            $command = sprintf(
                'mysqldump -h %s -u %s -p%s %s > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                return ['type' => 'error', 'message' => 'Export failed'];
            }

            return ['type' => 'success', 'message' => 'Exported successfully'];

        } catch (\Exception $e) {
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

