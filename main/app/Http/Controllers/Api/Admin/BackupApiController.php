<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * @group Admin - Backup Management
 *
 * Endpoints for database backup and restore operations.
 */
class BackupApiController extends Controller
{
    /**
     * List Backups
     *
     * Get all available database backups.
     *
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function index()
    {
        $backupPath = storage_path('app/backups');
        
        if (!file_exists($backupPath)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $files = scandir($backupPath);
        $backups = [];

        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $backups[] = [
                    'name' => $file,
                    'size' => filesize($backupPath . '/' . $file),
                    'created_at' => filectime($backupPath . '/' . $file),
                    'path' => 'backups/' . $file
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $backups
        ]);
    }

    /**
     * Create Backup
     *
     * Create a new database backup.
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Backup created successfully",
     *   "data": {...}
     * }
     */
    public function create()
    {
        try {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $backupPath = storage_path('app/backups');
            
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            $filepath = $backupPath . '/' . $filename;

            // Get database configuration
            $database = config('database.connections.' . config('database.default'));
            
            // Create backup using mysqldump
            $command = sprintf(
                'mysqldump -u%s -p%s %s > %s',
                $database['username'],
                $database['password'],
                $database['database'],
                $filepath
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create backup'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'data' => [
                    'name' => $filename,
                    'size' => filesize($filepath),
                    'path' => 'backups/' . $filename
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download Backup
     *
     * Download a backup file.
     *
     * @queryParam file string required Backup filename. Example: backup_2024-01-01_12-00-00.sql
     * @response 200 (binary file download)
     */
    public function download(Request $request)
    {
        $filename = $request->get('file');
        $filepath = storage_path('app/backups/' . $filename);

        if (!file_exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'Backup file not found'
            ], 404);
        }

        return response()->download($filepath);
    }

    /**
     * Delete Backup
     *
     * Delete a backup file.
     *
     * @bodyParam file string required Backup filename. Example: backup_2024-01-01_12-00-00.sql
     * @response 200 {
     *   "success": true,
     *   "message": "Backup deleted successfully"
     * }
     */
    public function delete(Request $request)
    {
        $filename = $request->input('file');
        $filepath = storage_path('app/backups/' . $filename);

        if (!file_exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'Backup file not found'
            ], 404);
        }

        unlink($filepath);

        return response()->json([
            'success' => true,
            'message' => 'Backup deleted successfully'
        ]);
    }

    /**
     * Restore Backup
     *
     * Restore database from a backup file.
     *
     * @bodyParam file string required Backup filename. Example: backup_2024-01-01_12-00-00.sql
     * @response 200 {
     *   "success": true,
     *   "message": "Database restored successfully"
     * }
     */
    public function restore(Request $request)
    {
        try {
            $filename = $request->input('file');
            $filepath = storage_path('app/backups/' . $filename);

            if (!file_exists($filepath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found'
                ], 404);
            }

            // Get database configuration
            $database = config('database.connections.' . config('database.default'));
            
            // Restore using mysql command
            $command = sprintf(
                'mysql -u%s -p%s %s < %s',
                $database['username'],
                $database['password'],
                $database['database'],
                $filepath
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to restore backup'
                ], 500);
            }

            // Clear cache after restore
            Artisan::call('cache:clear');
            Artisan::call('config:clear');

            return response()->json([
                'success' => true,
                'message' => 'Database restored successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore backup: ' . $e->getMessage()
            ], 500);
        }
    }
}
