<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Controllers\User;

use App\Http\Controllers\Controller;
use App\Helpers\NotificationHelper;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TradingPresetController extends Controller
{
    /**
     * Display a listing of the user's trading presets
     */
    public function index()
    {
        try {
            $title = 'My Trading Presets';
            
            if (!Schema::hasTable('trading_presets')) {
                Log::warning('Trading presets table does not exist');
                return view('trading-management::user.risk-management.presets.index', [
                    'presets' => collect([])->paginate(20),
                    'title' => $title
                ]);
            }
            
            $presets = TradingPreset::where(function($query) {
                $query->where('created_by_user_id', auth()->id())
                      ->orWhereNull('created_by_user_id');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
            return view('trading-management::user.risk-management.presets.index', compact('presets', 'title'));
        } catch (\Exception $e) {
            Log::error('Trading presets index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return view('trading-management::user.risk-management.presets.index', [
                'presets' => collect([])->paginate(20),
                'title' => 'My Trading Presets'
            ]);
        }
    }

    /**
     * Display marketplace presets
     */
    public function marketplace()
    {
        try {
            $title = 'Trading Presets Marketplace';
            $presets = TradingPreset::whereNull('created_by_user_id')
                ->where('visibility', 'PUBLIC_MARKETPLACE')
                ->orderBy('created_at', 'desc')
                ->paginate(20);
            return view('trading-management::user.risk-management.presets.marketplace', compact('presets', 'title'));
        } catch (\Exception $e) {
            Log::error('Trading presets marketplace error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return view('trading-management::user.risk-management.presets.marketplace', [
                'presets' => collect([])->paginate(20),
                'title' => 'Trading Presets Marketplace'
            ]);
        }
    }

    /**
     * Show the form for creating a new preset
     */
    public function create()
    {
        $title = 'Create Trading Preset';
        return view('trading-management::user.risk-management.presets.create', compact('title'));
    }

    /**
     * Show the form for editing the specified preset
     */
    public function edit($id)
    {
        try {
            $preset = TradingPreset::findOrFail($id);
            
            if ($preset->created_by_user_id !== auth()->id() && !is_null($preset->created_by_user_id)) {
                return back()->with('notify', NotificationHelper::error(__('You can only edit your own presets.'), 'Error'));
            }
            
            $title = 'Edit Trading Preset';
            return view('trading-management::user.risk-management.presets.edit', compact('preset', 'title'));
        } catch (\Exception $e) {
            Log::error('Trading preset edit error: ' . $e->getMessage());
            return back()->with('notify', NotificationHelper::error(__('Preset not found.'), 'Error'));
        }
    }

    /**
     * Clone a preset for the authenticated user
     */
    public function clone($id)
    {
        try {
            $preset = TradingPreset::findOrFail($id);
            
            if (!$preset->isPublic() || !$preset->isClonable()) {
                return back()->with('notify', NotificationHelper::error(__('This preset cannot be cloned.'), 'Error'));
            }
            
            if (method_exists($preset, 'cloneFor')) {
                $clonedPreset = $preset->cloneFor(auth()->user());
            } else {
                $clonedPreset = $preset->replicate();
                $clonedPreset->created_by_user_id = auth()->id();
                $clonedPreset->is_default_template = false;
                $clonedPreset->visibility = 'PRIVATE';
                $clonedPreset->name = $preset->name . ' (Copy)';
                $clonedPreset->save();
            }
            
            return back()->with('notify', NotificationHelper::success(__('Preset cloned successfully!'), 'Success'));
        } catch (\Exception $e) {
            Log::error('Trading preset clone error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('notify', NotificationHelper::error(__('Failed to clone preset. Please try again.'), 'Error'));
        }
    }
}
