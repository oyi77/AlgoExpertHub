<?php

namespace Addons\SmartRiskManagement\App\Http\Controllers\Backend;

use Addons\SmartRiskManagement\App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SrmSettingsController extends Controller
{
    /**
     * Display SRM settings.
     */
    public function index(): View
    {
        $data['title'] = 'SRM Settings';

        // Get current settings from config or database
        $data['settings'] = [
            'performance_score_threshold' => config('srm.performance_score_threshold', 40),
            'max_slippage_allowed' => config('srm.max_slippage_allowed', 10.0),
            'drawdown_threshold' => config('srm.drawdown_threshold', 20.0),
            'slippage_buffer_max' => config('srm.slippage_buffer_max', 3.0),
            'enable_srm' => config('srm.enable_srm', true),
        ];

        return view('smart-risk-management::backend.settings.index', $data);
    }

    /**
     * Update SRM settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'performance_score_threshold' => 'required|numeric|min:0|max:100',
            'max_slippage_allowed' => 'required|numeric|min:0|max:50',
            'drawdown_threshold' => 'required|numeric|min:1|max:100',
            'slippage_buffer_max' => 'required|numeric|min:0|max:10',
            'enable_srm' => 'nullable|boolean',
        ]);

        try {
            // Store settings in config file or database
            // For now, we'll use config file
            $configPath = config_path('srm.php');
            
            $config = [
                'performance_score_threshold' => $request->performance_score_threshold,
                'max_slippage_allowed' => $request->max_slippage_allowed,
                'drawdown_threshold' => $request->drawdown_threshold,
                'slippage_buffer_max' => $request->slippage_buffer_max,
                'enable_srm' => $request->has('enable_srm'),
            ];

            // Write to config file
            file_put_contents($configPath, "<?php\n\nreturn " . var_export($config, true) . ";\n");

            // Clear config cache
            Cache::forget('srm_settings');
            \Artisan::call('config:clear');

            return redirect()->back()->with('success', 'SRM settings updated successfully.');
        } catch (\Exception $e) {
            Log::error("SrmSettingsController: Failed to update settings", [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }
}

