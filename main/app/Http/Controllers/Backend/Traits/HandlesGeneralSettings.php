<?php

namespace App\Http\Controllers\Backend\Traits;

use App\Models\Configuration;
use App\Http\Requests\ConfigurationRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

trait HandlesGeneralSettings
{
    /**
     * Show configuration index page
     */
    public function index()
    {
        $data['title'] = 'Application Settings';

        // Ensure configuration exists
        $data['general'] = Configuration::first();
        if (!$data['general']) {
            // Create default configuration if it doesn't exist
            $data['general'] = Configuration::create([
                'id' => 1,
                'appname' => config('app.name', 'AlgoExpertHub'),
                'currency' => 'USD',
                'decimal_precision' => 2,
            ]);
        }

        $data['timezone'] = json_decode(file_get_contents(resource_path('views/backend/setting/timezone.json')));
        
        // Get cron job settings dynamically
        $data['cronJobs'] = $this->getCronJobs();
        
        // Get dynamic performance tips based on codebase analysis
        $data['performanceTips'] = $this->getPerformanceTips();
        
        // Get database backups
        $data['backups'] = $this->backupService->listBackups();
        
        // Get dynamic seeder count
        $data['seederCount'] = $this->getSeederCount();
        
        return view('backend.setting.index')->with($data);
    }

    /**
     * Update general configuration
     */
    public function ConfigurationUpdate(ConfigurationRequest $request)
    {
        try {
            $isSuccess = $this->config->general($request);

            if ($isSuccess['type'] == 'success') {
                // Clear cache and redirect explicitly to avoid stale data
                Cache::forget('app_configuration');
                return redirect()->route('admin.general.index')->with('success', $isSuccess['message']);
            } else {
                return redirect()->route('admin.general.index')->with('error', $isSuccess['message'] ?? 'Failed to update settings.');
            }
        } catch (\Exception $e) {
            \Log::error('Configuration update error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            // Clear cache even on error to prevent stale data
            Cache::forget('app_configuration');
            return redirect()->route('admin.general.index')->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    /**
     * Clear all caches
     */
    public function cacheClear()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('optimize:clear');

        return back()->with('success', 'Caches cleared successfully!');
    }
}

