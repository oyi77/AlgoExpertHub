<?php

namespace Addons\TradingPresetAddon\App\Http\Controllers\User;

use Addons\TradingPresetAddon\App\Http\Controllers\Controller;
use Addons\TradingPresetAddon\App\Models\TradingPreset;
use Addons\TradingPresetAddon\App\Services\PresetService;
use Addons\TradingPresetAddon\App\Services\PresetValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PresetController extends Controller
{
    protected PresetService $presetService;
    protected PresetValidationService $validationService;

    public function __construct(
        PresetService $presetService,
        PresetValidationService $validationService
    ) {
        $this->presetService = $presetService;
        $this->validationService = $validationService;
    }

    /**
     * Display a listing of user's presets.
     */
    public function index(Request $request): View
    {
        $data['title'] = 'My Trading Presets';

        $user = Auth::user();

        // Get user's own presets
        $query = TradingPreset::byUser($user->id);

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('symbol', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by enabled
        if ($request->has('enabled')) {
            $query->where('enabled', $request->enabled);
        }

        $data['presets'] = $query->latest()->paginate(20);

        $data['stats'] = [
            'total' => TradingPreset::byUser($user->id)->count(),
            'enabled' => TradingPreset::byUser($user->id)->enabled()->count(),
            'disabled' => TradingPreset::byUser($user->id)->where('enabled', false)->count(),
        ];

        return view('trading-preset-addon::user.presets.index', $data);
    }

    /**
     * Display marketplace (public and default presets).
     */
    public function marketplace(Request $request): View
    {
        $data['title'] = 'Preset Marketplace';

        $query = TradingPreset::public()
            ->orWhere(function ($q) {
                $q->defaultTemplates();
            })
            ->enabled();

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('symbol', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by symbol
        if ($request->symbol) {
            $query->bySymbol($request->symbol);
        }

        // Filter by timeframe
        if ($request->timeframe) {
            $query->byTimeframe($request->timeframe);
        }

        $data['presets'] = $query->latest()->paginate(20);

        return view('trading-preset-addon::user.presets.marketplace', $data);
    }

    /**
     * Show the form for creating a new preset.
     */
    public function create(): View
    {
        $data['title'] = 'Create Trading Preset';
        $data['preset'] = null;
        
        // Get available filter strategies (Sprint 1: Filter Strategy)
        if (class_exists(\Addons\FilterStrategyAddon\App\Models\FilterStrategy::class)) {
            $user = Auth::user();
            $data['filterStrategies'] = \Addons\FilterStrategyAddon\App\Models\FilterStrategy::where(function ($query) use ($user) {
                $query->where('created_by_user_id', $user->id)
                    ->orWhere('visibility', 'PUBLIC_MARKETPLACE');
            })
            ->where('enabled', true)
            ->orderBy('name')
            ->get();
        } else {
            $data['filterStrategies'] = collect();
        }

        // Get available AI Model Profiles (Sprint 2: AI Trading)
        if (class_exists(\Addons\AiTradingAddon\App\Models\AiModelProfile::class)) {
            $user = Auth::user();
            $data['aiModelProfiles'] = \Addons\AiTradingAddon\App\Models\AiModelProfile::where(function ($query) use ($user) {
                $query->where('created_by_user_id', $user->id)
                    ->orWhere('visibility', 'PUBLIC_MARKETPLACE');
            })
            ->where('enabled', true)
            ->orderBy('name')
            ->get();
        } else {
            $data['aiModelProfiles'] = collect();
        }

        return view('trading-preset-addon::user.presets.create', $data);
    }

    /**
     * Store a newly created preset.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->all();

        // User presets are always private
        $data['visibility'] = 'PRIVATE';
        $data['is_default_template'] = false;

        // Validate
        $validation = $this->validationService->validate($data);
        if (!$validation['valid']) {
            return redirect()->back()
                ->withInput()
                ->withErrors($validation['errors']);
        }

        // Get warnings
        $warnings = $this->validationService->getWarnings($data);

        try {
            $preset = $this->presetService->create($data, Auth::user());

            $message = 'Preset created successfully.';
            if (!empty($warnings)) {
                $message .= ' Warnings: ' . implode(', ', $warnings);
            }

            return redirect()->route('user.trading-presets.index')
                ->with('success', $message)
                ->with('warnings', $warnings);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create preset: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified preset.
     */
    public function show(int $id): View
    {
        $preset = $this->presetService->findOrFail($id);
        $user = Auth::user();

        // Check if user can view this preset
        if ($preset->isPrivate() && $preset->created_by_user_id !== $user->id) {
            abort(403, 'You do not have permission to view this preset.');
        }

        $data['preset'] = $preset;
        $data['title'] = 'View Preset: ' . $preset->name;
        $data['canEdit'] = $preset->canBeEditedBy($user);
        $data['canClone'] = $preset->canBeClonedBy($user);

        // Get usage statistics (only for own presets)
        if ($preset->created_by_user_id === $user->id) {
            $data['usage'] = [
                'connections' => $preset->executionConnections()->count(),
                'subscriptions' => $preset->copyTradingSubscriptions()->count(),
            ];
        }

        return view('trading-preset-addon::user.presets.show', $data);
    }

    /**
     * Show the form for editing the specified preset.
     */
    public function edit(int $id): View
    {
        $preset = $this->presetService->findOrFail($id);
        $user = Auth::user();

        // Check permissions
        if (!$preset->canBeEditedBy($user)) {
            abort(403, 'You do not have permission to edit this preset.');
        }

        $data['preset'] = $preset;
        $data['title'] = 'Edit Preset: ' . $preset->name;
        
        // Get available filter strategies (Sprint 1: Filter Strategy)
        if (class_exists(\Addons\FilterStrategyAddon\App\Models\FilterStrategy::class)) {
            $user = Auth::user();
            $data['filterStrategies'] = \Addons\FilterStrategyAddon\App\Models\FilterStrategy::where(function ($query) use ($user) {
                $query->where('created_by_user_id', $user->id)
                    ->orWhere('visibility', 'PUBLIC_MARKETPLACE');
            })
            ->where('enabled', true)
            ->orderBy('name')
            ->get();
        } else {
            $data['filterStrategies'] = collect();
        }

        // Get available AI Model Profiles (Sprint 2: AI Trading)
        if (class_exists(\Addons\AiTradingAddon\App\Models\AiModelProfile::class)) {
            $user = Auth::user();
            $data['aiModelProfiles'] = \Addons\AiTradingAddon\App\Models\AiModelProfile::where(function ($query) use ($user) {
                $query->where('created_by_user_id', $user->id)
                    ->orWhere('visibility', 'PUBLIC_MARKETPLACE');
            })
            ->where('enabled', true)
            ->orderBy('name')
            ->get();
        } else {
            $data['aiModelProfiles'] = collect();
        }

        return view('trading-preset-addon::user.presets.edit', $data);
    }

    /**
     * Update the specified preset.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $preset = $this->presetService->findOrFail($id);
        $user = Auth::user();

        $data = $request->all();

        // Users cannot change visibility to PUBLIC_MARKETPLACE or set as default template
        unset($data['visibility']);
        unset($data['is_default_template']);

        // Validate
        $validation = $this->validationService->validate($data);
        if (!$validation['valid']) {
            return redirect()->back()
                ->withInput()
                ->withErrors($validation['errors']);
        }

        // Get warnings
        $warnings = $this->validationService->getWarnings($data);

        try {
            $this->presetService->update($preset, $data, $user);

            $message = 'Preset updated successfully.';
            if (!empty($warnings)) {
                $message .= ' Warnings: ' . implode(', ', $warnings);
            }

            return redirect()->route('user.trading-presets.index')
                ->with('success', $message)
                ->with('warnings', $warnings);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update preset: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified preset.
     */
    public function destroy(int $id): RedirectResponse
    {
        $preset = $this->presetService->findOrFail($id);
        $user = Auth::user();

        try {
            $this->presetService->delete($preset, $user);

            return redirect()->route('user.trading-presets.index')
                ->with('success', 'Preset deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete preset: ' . $e->getMessage());
        }
    }

    /**
     * Clone a preset.
     */
    public function clone(int $id, Request $request): RedirectResponse
    {
        $preset = $this->presetService->findOrFail($id);
        $user = Auth::user();

        $newName = $request->input('name');

        try {
            $cloned = $this->presetService->clone($preset, $user, $newName);

            return redirect()->route('user.trading-presets.edit', $cloned->id)
                ->with('success', 'Preset cloned successfully. You can now edit it.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to clone preset: ' . $e->getMessage());
        }
    }

    /**
     * Set default preset for user.
     */
    public function setDefault(int $id): RedirectResponse
    {
        $preset = $this->presetService->findOrFail($id);
        $user = Auth::user();

        // Check if user can use this preset
        if ($preset->isPrivate() && $preset->created_by_user_id !== $user->id) {
            return redirect()->back()
                ->with('error', 'You cannot set this preset as default.');
        }

        try {
            $user->default_preset_id = $preset->id;
            $user->save();

            return redirect()->back()
                ->with('success', 'Default preset updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to set default preset: ' . $e->getMessage());
        }
    }

    /**
     * Toggle preset enabled status.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        $preset = $this->presetService->findOrFail($id);
        $user = Auth::user();

        // Check permissions
        if (!$preset->canBeEditedBy($user)) {
            return redirect()->back()
                ->with('error', 'You do not have permission to modify this preset.');
        }

        try {
            $preset->enabled = !$preset->enabled;
            $preset->save();

            $status = $preset->enabled ? 'enabled' : 'disabled';

            return redirect()->back()
                ->with('success', "Preset {$status} successfully.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to toggle preset status: ' . $e->getMessage());
        }
    }
}

