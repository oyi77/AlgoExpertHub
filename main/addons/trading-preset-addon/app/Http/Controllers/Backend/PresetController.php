<?php

namespace Addons\TradingPresetAddon\App\Http\Controllers\Backend;

use Addons\TradingPresetAddon\App\Http\Controllers\Controller;
use Addons\TradingPresetAddon\App\Models\TradingPreset;
use Addons\TradingPresetAddon\App\Services\PresetService;
use Addons\TradingPresetAddon\App\Services\PresetValidationService;
use App\Helpers\Helper\Helper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * Display a listing of presets.
     */
    public function index(Request $request): View
    {
        $data['title'] = 'Trading Presets';

        $query = TradingPreset::with('creator');

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('symbol', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by visibility
        if ($request->visibility) {
            $query->where('visibility', $request->visibility);
        }

        // Filter by default template
        if ($request->has('is_default')) {
            $query->where('is_default_template', $request->is_default);
        }

        // Filter by enabled
        if ($request->has('enabled')) {
            $query->where('enabled', $request->enabled);
        }

        $data['presets'] = $query->latest()->paginate(Helper::pagination());

        $data['stats'] = [
            'total' => TradingPreset::count(),
            'default' => TradingPreset::defaultTemplates()->count(),
            'public' => TradingPreset::public()->count(),
            'private' => TradingPreset::private()->count(),
            'enabled' => TradingPreset::enabled()->count(),
            'disabled' => TradingPreset::where('enabled', false)->count(),
        ];

        return view('trading-preset-addon::backend.presets.index', $data);
    }

    /**
     * Show the form for creating a new preset.
     */
    public function create(): View
    {
        $data['title'] = 'Create Trading Preset';
        $data['preset'] = null;

        return view('trading-preset-addon::backend.presets.create', $data);
    }

    /**
     * Store a newly created preset.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->all();

        // Set as default template (admin only)
        if ($request->has('is_default_template')) {
            $data['is_default_template'] = true;
        }

        // Set visibility
        $data['visibility'] = $request->input('visibility', 'PRIVATE');

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
            $preset = $this->presetService->create($data, auth()->user());

            $message = 'Preset created successfully.';
            if (!empty($warnings)) {
                $message .= ' Warnings: ' . implode(', ', $warnings);
            }

            return redirect()->route('admin.trading-presets.index')
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
        $data['preset'] = $this->presetService->findOrFail($id);
        $data['title'] = 'View Preset: ' . $data['preset']->name;

        // Get usage statistics
        $data['usage'] = [
            'connections' => $data['preset']->executionConnections()->count(),
            'subscriptions' => $data['preset']->copyTradingSubscriptions()->count(),
            'users_with_default' => $data['preset']->usersWithDefault()->count(),
        ];

        return view('trading-preset-addon::backend.presets.show', $data);
    }

    /**
     * Show the form for editing the specified preset.
     */
    public function edit(int $id): View
    {
        $data['preset'] = $this->presetService->findOrFail($id);
        $data['title'] = 'Edit Preset: ' . $data['preset']->name;

        return view('trading-preset-addon::backend.presets.edit', $data);
    }

    /**
     * Update the specified preset.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $preset = $this->presetService->findOrFail($id);

        $data = $request->all();

        // Set as default template (admin only)
        if ($request->has('is_default_template')) {
            $data['is_default_template'] = true;
        }

        // Set visibility
        if ($request->has('visibility')) {
            $data['visibility'] = $request->input('visibility');
        }

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
            $this->presetService->update($preset, $data, auth()->user());

            $message = 'Preset updated successfully.';
            if (!empty($warnings)) {
                $message .= ' Warnings: ' . implode(', ', $warnings);
            }

            return redirect()->route('admin.trading-presets.index')
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

        try {
            $this->presetService->delete($preset, auth()->user());

            return redirect()->route('admin.trading-presets.index')
                ->with('success', 'Preset deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete preset: ' . $e->getMessage());
        }
    }

    /**
     * Clone a preset.
     */
    public function clone(int $id): RedirectResponse
    {
        $preset = $this->presetService->findOrFail($id);

        try {
            $cloned = $this->presetService->clone($preset, auth()->user());

            return redirect()->route('admin.trading-presets.edit', $cloned->id)
                ->with('success', 'Preset cloned successfully. You can now edit it.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to clone preset: ' . $e->getMessage());
        }
    }

    /**
     * Toggle preset enabled status.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        $preset = $this->presetService->findOrFail($id);

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

