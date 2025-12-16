<?php

namespace Addons\AlgoExpertPlus\App\Http\Controllers\Backend;

use Addons\AlgoExpertPlus\App\Http\Controllers\Controller;
use Addons\AlgoExpertPlus\App\Services\BackupService;
use App\Services\DatabaseBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Spatie\Backup\BackupDestination\BackupDestination;

class BackupController extends Controller
{
    protected $backupService;
    protected $databaseBackupService;

    public function __construct(BackupService $backupService, DatabaseBackupService $databaseBackupService)
    {
        $this->backupService = $backupService;
        $this->databaseBackupService = $databaseBackupService;
    }

    /**
     * Display backup dashboard with historical backups
     */
    public function index(): View
    {
        // Get Spatie backups (full system backups)
        $spatieBackups = [];
        $spatieStats = [
            'total_count' => 0,
            'total_size' => 0,
            'total_size_human' => '0 B',
            'oldest_backup' => null,
            'newest_backup' => null,
        ];

        if ($this->backupService->isAvailable()) {
            try {
                $spatieBackups = $this->listBackups();
                $spatieStats = $this->getBackupStats($spatieBackups);
            } catch (\Throwable $e) {
                \Log::warning('Failed to list Spatie backups', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Get SQL database backups
        $sqlBackups = [];
        $sqlStats = [
            'total_count' => 0,
            'total_size' => 0,
            'total_size_human' => '0 B',
            'oldest_backup' => null,
            'newest_backup' => null,
        ];

        try {
            $sqlBackups = $this->databaseBackupService->listBackups();
            $sqlStats = $this->getSqlBackupStats($sqlBackups);
        } catch (\Throwable $e) {
            \Log::warning('Failed to list SQL backups', [
                'error' => $e->getMessage(),
            ]);
        }

        // Get seeder count for factory state info
        $seederCount = $this->getSeederCount();

        $data = [
            'title' => 'Backup Dashboard',
            'spatieBackups' => $spatieBackups,
            'spatieStats' => $spatieStats,
            'sqlBackups' => $sqlBackups,
            'sqlStats' => $sqlStats,
            'seederCount' => $seederCount,
            'spatieAvailable' => $this->backupService->isAvailable(),
        ];

        return view('algoexpert-plus::backend.backup.index', $data);
    }

    /**
     * List all backups from all destinations
     */
    protected function listBackups(): array
    {
        $allBackups = [];

        try {
            // Get backup disks from config
            $backupDisks = config('backup.backup.destination.disks', ['local']);
            $backupName = config('backup.backup.name', env('APP_NAME', 'laravel-backup'));
            
            if (empty($backupDisks) || !is_array($backupDisks)) {
                \Log::warning('Invalid backup disks configuration', ['disks' => $backupDisks]);
                return [];
            }
            
            foreach ($backupDisks as $diskName) {
                try {
                    if (empty($diskName)) {
                        continue;
                    }
                    $backupDestination = BackupDestination::create($diskName, $backupName);
                    $backups = $backupDestination->backups();

                    foreach ($backups as $backup) {
                        try {
                            // Get size from storage disk (Backup object doesn't have size() method in v8)
                            $storage = Storage::disk($diskName);
                            $backupPath = $backup->path();
                            $backupSize = $storage->exists($backupPath) ? $storage->size($backupPath) : 0;
                            
                            $allBackups[] = [
                                'path' => $backupPath,
                                'disk' => $diskName,
                                'name' => $backupPath,
                                'size' => $backupSize,
                                'size_human' => $this->formatBytes($backupSize),
                                'date' => $backup->date(),
                                'date_human' => $backup->date()->format('Y-m-d H:i:s'),
                                'age_days' => $backup->date()->diffInDays(now()),
                                'age_human' => $backup->date()->diffForHumans(),
                            ];
                        } catch (\Throwable $e) {
                            \Log::warning('Failed to process backup entry', [
                                'disk' => $diskName,
                                'error' => $e->getMessage(),
                            ]);
                            continue;
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Failed to list backups from disk', [
                        'disk' => $diskName,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Sort by date (newest first)
            usort($allBackups, function ($a, $b) {
                return $b['date']->timestamp <=> $a['date']->timestamp;
            });
        } catch (\Throwable $e) {
            \Log::error('Failed to list backups', ['error' => $e->getMessage()]);
        }

        return $allBackups;
    }

    /**
     * Get backup statistics
     */
    protected function getBackupStats(array $backups): array
    {
        $totalSize = 0;
        $totalCount = count($backups);
        $oldestBackup = null;
        $newestBackup = null;

        foreach ($backups as $backup) {
            $totalSize += $backup['size'];
            if (!$oldestBackup || $backup['date'] < $oldestBackup['date']) {
                $oldestBackup = $backup;
            }
            if (!$newestBackup || $backup['date'] > $newestBackup['date']) {
                $newestBackup = $backup;
            }
        }

        return [
            'total_count' => $totalCount,
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'oldest_backup' => $oldestBackup,
            'newest_backup' => $newestBackup,
        ];
    }

    /**
     * Download a backup file
     */
    public function download(Request $request)
    {
        $path = $request->input('path');
        $disk = $request->input('disk', 'local');

        if (!$path) {
            return redirect()->back()->with('error', 'Backup path is required');
        }

        try {
            $storage = Storage::disk($disk);
            
            if (!$storage->exists($path)) {
                return redirect()->back()->with('error', 'Backup file not found');
            }

            return $storage->download($path);
        } catch (\Throwable $e) {
            \Log::error('Failed to download backup', [
                'path' => $path,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to download backup: ' . $e->getMessage());
        }
    }

    /**
     * Delete a backup file
     */
    public function delete(Request $request)
    {
        $path = $request->input('path');
        $disk = $request->input('disk', 'local');

        if (!$path) {
            $message = 'Backup path is required';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return redirect()->back()->with('error', $message);
        }

        try {
            $storage = Storage::disk($disk);
            
            if (!$storage->exists($path)) {
                $message = 'Backup file not found';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $message], 404);
                }
                return redirect()->back()->with('error', $message);
            }

            $storage->delete($path);

            $message = 'Backup deleted successfully';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            return redirect()->back()->with('success', $message);
        } catch (\Throwable $e) {
            \Log::error('Failed to delete backup', [
                'path' => $path,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);

            $message = 'Failed to delete backup: ' . $e->getMessage();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Run a new backup
     */
    public function run()
    {
        try {
            $result = $this->backupService->run();
            
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json($result);
            }
            
            return redirect()->route('admin.algoexpert-plus.backup.index')
                ->with('success', $result['message'] ?? 'Backup started successfully');
        } catch (\Throwable $e) {
            $message = 'Backup failed: ' . $e->getMessage();
            
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            
            return redirect()->route('admin.algoexpert-plus.backup.index')
                ->with('error', $message);
        }
    }

    /**
     * Clean old backups
     */
    public function clean()
    {
        try {
            \Artisan::call('backup:clean');
            $output = \Artisan::output();
            
            $message = 'Backup cleanup completed. ' . $output;
            
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => true, 'message' => $message, 'output' => $output]);
            }
            
            return redirect()->route('admin.algoexpert-plus.backup.index')
                ->with('success', $message);
        } catch (\Throwable $e) {
            $message = 'Backup cleanup failed: ' . $e->getMessage();
            
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            
            return redirect()->route('admin.algoexpert-plus.backup.index')
                ->with('error', $message);
        }
    }

    /**
     * Get SQL backup statistics
     */
    protected function getSqlBackupStats(array $backups): array
    {
        $totalSize = 0;
        $totalCount = count($backups);
        $oldestBackup = null;
        $newestBackup = null;

        foreach ($backups as $backup) {
            $totalSize += $backup['size'];
            $backupDate = \Carbon\Carbon::parse($backup['created_at']);
            
            if (!$oldestBackup || $backupDate < \Carbon\Carbon::parse($oldestBackup['created_at'])) {
                $oldestBackup = $backup;
            }
            if (!$newestBackup || $backupDate > \Carbon\Carbon::parse($newestBackup['created_at'])) {
                $newestBackup = $backup;
            }
        }

        return [
            'total_count' => $totalCount,
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'oldest_backup' => $oldestBackup,
            'newest_backup' => $newestBackup,
        ];
    }

    /**
     * Create SQL database backup
     */
    public function createSqlBackup(Request $request)
    {
        try {
            set_time_limit(300);
            
            $name = $request->input('backup_name') ?? 'backup_' . date('Y-m-d_H-i-s');
            $result = $this->databaseBackupService->createBackup($name);
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => $result['type'] === 'success',
                    'message' => $result['message'],
                    'refresh' => true
                ]);
            }
            
            return redirect()->back()->with($result['type'], $result['message']);
        } catch (\Throwable $e) {
            $message = 'Backup failed: ' . $e->getMessage();
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            
            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Load SQL database backup
     */
    public function loadSqlBackup(Request $request)
    {
        try {
            if (empty($request->input('backup_file'))) {
                $message = 'Please select a backup file';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $message], 400);
                }
                return redirect()->back()->with('error', $message);
            }

            set_time_limit(600);
            
            $result = $this->databaseBackupService->loadBackup($request->input('backup_file'));
            
            if ($result['type'] === 'success') {
                $message = 'Database restored successfully! Please login again.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'redirect' => route('admin.login')
                    ]);
                }
                return redirect()->route('admin.login')->with('success', $message);
            }
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }
            
            return redirect()->back()->with($result['type'], $result['message']);
        } catch (\Throwable $e) {
            $message = 'Restore failed: ' . $e->getMessage();
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            
            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Delete SQL database backup
     */
    public function deleteSqlBackup(Request $request)
    {
        try {
            if (empty($request->input('backup_file'))) {
                $message = 'Please select a backup file';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $message], 400);
                }
                return redirect()->back()->with('error', $message);
            }

            $result = $this->databaseBackupService->deleteBackup($request->input('backup_file'));
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => $result['type'] === 'success',
                    'message' => $result['message'],
                    'refresh' => true
                ]);
            }
            
            return redirect()->back()->with($result['type'], $result['message']);
        } catch (\Throwable $e) {
            $message = 'Delete failed: ' . $e->getMessage();
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            
            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Download SQL database backup
     */
    public function downloadSqlBackup(Request $request)
    {
        $filename = $request->input('backup_file');
        
        if (!$filename) {
            return redirect()->back()->with('error', 'Backup filename is required');
        }

        try {
            $backupPath = storage_path('app/database-backups/' . $filename);
            
            if (!File::exists($backupPath)) {
                return redirect()->back()->with('error', 'Backup file not found');
            }

            return response()->download($backupPath);
        } catch (\Throwable $e) {
            \Log::error('Failed to download SQL backup', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to download backup: ' . $e->getMessage());
        }
    }

    /**
     * Save backup as factory state
     */
    public function saveAsFactoryState(Request $request)
    {
        try {
            $result = $this->databaseBackupService->saveAsFactoryState($request->input('backup_file'));
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => $result['type'] === 'success',
                    'message' => $result['message'],
                    'refresh' => true
                ]);
            }
            
            return redirect()->back()->with($result['type'], $result['message']);
        } catch (\Throwable $e) {
            $message = 'Save failed: ' . $e->getMessage();
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            
            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Load factory state
     */
    public function loadFactoryState(Request $request)
    {
        try {
            set_time_limit(600);

            $result = $this->databaseBackupService->loadFactoryState();

            if ($result['type'] === 'success') {
                $message = 'Restored to factory state! Please login again.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'redirect' => route('admin.login')
                    ]);
                }
                return redirect()->route('admin.login')->with('success', $message);
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            return redirect()->back()->with($result['type'], $result['message']);
        } catch (\Throwable $e) {
            $message = 'Factory restore failed: ' . $e->getMessage();
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            
            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Get seeder count
     */
    protected function getSeederCount(): int
    {
        try {
            $seederFile = database_path('seeders/DatabaseSeeder.php');
            
            if (!file_exists($seederFile)) {
                return 0;
            }

            $content = file_get_contents($seederFile);
            $count = substr_count($content, 'Seeder::class') + substr_count($content, 'RolePermission::class');
            
            return $count;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
