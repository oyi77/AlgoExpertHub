<?php

namespace Addons\AlgoExpertPlus\App\Http\Controllers\Backend;

use Addons\AlgoExpertPlus\App\Http\Controllers\Controller;
use Addons\AlgoExpertPlus\App\Services\BackupService;
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

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Display backup dashboard with historical backups
     */
    public function index(): View
    {
        if (!$this->backupService->isAvailable()) {
            abort(503, 'Backup service is not available. Please install spatie/laravel-backup package.');
        }

        $backups = $this->listBackups();
        $stats = $this->getBackupStats($backups);

        $data = [
            'title' => 'Backup Dashboard',
            'backups' => $backups,
            'stats' => $stats,
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
            
            foreach ($backupDisks as $diskName) {
                try {
                    $backupDestination = BackupDestination::create($diskName, config('backup.backup.name'));
                    $backups = $backupDestination->backups();

                    foreach ($backups as $backup) {
                        $allBackups[] = [
                            'path' => $backup->path(),
                            'disk' => $diskName,
                            'name' => $backup->path(),
                            'size' => $backup->size(),
                            'size_human' => $this->formatBytes($backup->size()),
                            'date' => $backup->date(),
                            'date_human' => $backup->date()->format('Y-m-d H:i:s'),
                            'age_days' => $backup->date()->diffInDays(now()),
                            'age_human' => $backup->date()->diffForHumans(),
                        ];
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
    public function download(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse|RedirectResponse
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
    public function delete(Request $request): JsonResponse|RedirectResponse
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
    public function run(): JsonResponse|RedirectResponse
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
    public function clean(): JsonResponse|RedirectResponse
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
