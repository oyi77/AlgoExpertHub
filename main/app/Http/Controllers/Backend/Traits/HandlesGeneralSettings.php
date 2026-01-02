<?php

namespace App\Http\Controllers\Backend\Traits;

use App\Helpers\NotificationHelper;
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

            // Clear cache
            Cache::forget('app_configuration');

            // Handle AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                if ($isSuccess['type'] == 'success') {
                    return response()->json([
                        'type' => 'success',
                        'message' => $isSuccess['message'],
                        'title' => 'Success'
                    ]);
                } else {
                    return response()->json([
                        'type' => 'error',
                        'message' => $isSuccess['message'] ?? 'Failed to update settings.',
                        'title' => 'Error'
                    ], 400);
                }
            }

            // Handle regular form submissions (fallback)
            if ($isSuccess['type'] == 'success') {
                return redirect()->route('admin.general.index')->with('notify', NotificationHelper::success($isSuccess['message'], 'Success'));
            } else {
                return redirect()->route('admin.general.index')->with('notify', NotificationHelper::error($isSuccess['message'] ?? 'Failed to update settings.', 'Error'));
            }
        } catch (\Exception $e) {
            \Log::error('Configuration update error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            // Clear cache even on error to prevent stale data
            Cache::forget('app_configuration');

            // Handle AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'Failed to update settings: ' . $e->getMessage(),
                    'title' => 'Error'
                ], 500);
            }

            return redirect()->route('admin.general.index')->with('notify', NotificationHelper::error('Failed to update settings: ' . $e->getMessage(), 'Error'));
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

        return back()->with('notify', NotificationHelper::success('Caches cleared successfully!', 'Success'));
    }
}

