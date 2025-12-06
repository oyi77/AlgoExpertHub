<?php

namespace Addons\AlgoExpertPlus\App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class BackupService
{
    /**
     * Check if backup service is available
     */
    public function isAvailable(): bool
    {
        return class_exists(\Spatie\Backup\BackupServiceProvider::class);
    }

    /**
     * Run system backup
     */
    public function run(): array
    {
        if (!$this->isAvailable()) {
            throw new \Exception('Backup service is not available. Please install spatie/laravel-backup package.');
        }

        try {
            Artisan::call('backup:run');
            Log::info('Backup started via AlgoExpert++ addon');
            
            return [
                'success' => true,
                'message' => 'Backup started successfully',
            ];
        } catch (\Throwable $e) {
            Log::error('Backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Get backup status
     */
    public function getStatus(): array
    {
        if (!$this->isAvailable()) {
            return [
                'available' => false,
                'message' => 'Package not installed. Run: composer install',
            ];
        }

        return [
            'available' => true,
            'message' => 'Backup service ready',
        ];
    }
}
