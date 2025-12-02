<?php

namespace Addons\AiTradingAddon\App\Http\Controllers\Backend;

use Addons\AiTradingAddon\App\Models\AiModelProfile;
use Addons\AiTradingAddon\App\Services\AiModelProfileService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiModelProfileController extends Controller
{
    protected AiModelProfileService $service;

    public function __construct(AiModelProfileService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $profiles = AiModelProfile::with('owner:id,username')
                ->withCount('tradingPresets')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            // Optimize stats calculation
            $stats = [
                'total' => AiModelProfile::count(),
                'enabled' => AiModelProfile::where('enabled', true)->count(),
                'public' => AiModelProfile::where('visibility', 'PUBLIC_MARKETPLACE')->count(),
            ];
        } catch (\Exception $e) {
            \Log::error('Error loading AI model profiles', ['error' => $e->getMessage()]);
            $profiles = collect([])->paginate(20);
            $stats = [
                'total' => 0,
                'enabled' => 0,
                'public' => 0,
            ];
        }

        $title = 'AI Model Profiles';

        return view('ai-trading-addon::backend.ai-model-profiles.index', compact('profiles', 'stats', 'title'));
    }

    public function create()
    {
        $title = 'Create AI Model Profile';
        return view('ai-trading-addon::backend.ai-model-profiles.create', compact('title'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:PRIVATE,PUBLIC_MARKETPLACE',
            'clonable' => 'boolean',
            'enabled' => 'boolean',
            'provider' => 'required|string',
            'model_name' => 'required|string',
            'api_key_ref' => 'nullable|string',
            'mode' => 'required|in:CONFIRM,SCAN,POSITION_MGMT',
            'prompt_template' => 'nullable|string',
            'settings' => 'nullable|json',
            'max_calls_per_minute' => 'nullable|integer|min:1',
            'max_calls_per_day' => 'nullable|integer|min:1',
        ]);

        try {
            $profile = $this->service->create($validated, auth()->guard('admin')->user());
            return redirect()->route('admin.ai-model-profiles.index')
                ->with('success', 'AI Model Profile created successfully');
        } catch (\Exception $e) {
            Log::error('Failed to create AI model profile', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create AI model profile: ' . $e->getMessage());
        }
    }

    public function show(AiModelProfile $aiModelProfile)
    {
        $aiModelProfile->load('owner');
        $title = 'AI Model Profile Details';
        return view('ai-trading-addon::backend.ai-model-profiles.show', compact('aiModelProfile', 'title'));
    }

    public function edit(AiModelProfile $aiModelProfile)
    {
        $title = 'Edit AI Model Profile';
        return view('ai-trading-addon::backend.ai-model-profiles.edit', compact('aiModelProfile', 'title'));
    }

    public function update(Request $request, AiModelProfile $aiModelProfile)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:PRIVATE,PUBLIC_MARKETPLACE',
            'clonable' => 'boolean',
            'enabled' => 'boolean',
            'provider' => 'required|string',
            'model_name' => 'required|string',
            'api_key_ref' => 'nullable|string',
            'mode' => 'required|in:CONFIRM,SCAN,POSITION_MGMT',
            'prompt_template' => 'nullable|string',
            'settings' => 'nullable|json',
            'max_calls_per_minute' => 'nullable|integer|min:1',
            'max_calls_per_day' => 'nullable|integer|min:1',
        ]);

        try {
            $this->service->update($aiModelProfile, $validated, auth()->guard('admin')->user());
            return redirect()->route('admin.ai-model-profiles.index')
                ->with('success', 'AI Model Profile updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update AI model profile', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update AI model profile: ' . $e->getMessage());
        }
    }

    public function destroy(AiModelProfile $aiModelProfile)
    {
        try {
            $this->service->delete($aiModelProfile, auth()->guard('admin')->user());
            return redirect()->route('admin.ai-model-profiles.index')
                ->with('success', 'AI Model Profile deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete AI model profile', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to delete AI model profile: ' . $e->getMessage());
        }
    }
}

