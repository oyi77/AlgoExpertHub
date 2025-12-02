<?php

namespace Addons\AiTradingAddon\App\Http\Controllers\User;

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
        $user = auth()->user();
        $profiles = AiModelProfile::where('created_by_user_id', $user->id)
            ->orWhere(function ($query) {
                $query->where('visibility', 'PUBLIC_MARKETPLACE');
            })
            ->with('owner')
            ->withCount('tradingPresets')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('ai-trading-addon::user.ai-model-profiles.index', compact('profiles'));
    }

    public function marketplace()
    {
        $profiles = AiModelProfile::where('visibility', 'PUBLIC_MARKETPLACE')
            ->with('owner')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('ai-trading-addon::user.ai-model-profiles.marketplace', compact('profiles'));
    }

    public function create()
    {
        return view('ai-trading-addon::user.ai-model-profiles.create');
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
        ]);

        try {
            $profile = $this->service->create($validated, auth()->user());
            return redirect()->route('user.ai-model-profiles.index')
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
        return view('ai-trading-addon::user.ai-model-profiles.show', compact('aiModelProfile'));
    }

    public function edit(AiModelProfile $aiModelProfile)
    {
        $user = auth()->user();
        if (!$aiModelProfile->canEditBy($user->id)) {
            abort(403, 'You do not have permission to edit this profile');
        }

        return view('ai-trading-addon::user.ai-model-profiles.edit', compact('aiModelProfile'));
    }

    public function update(Request $request, AiModelProfile $aiModelProfile)
    {
        $user = auth()->user();
        if (!$aiModelProfile->canEditBy($user->id)) {
            abort(403, 'You do not have permission to update this profile');
        }

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
        ]);

        try {
            $this->service->update($aiModelProfile, $validated, $user);
            return redirect()->route('user.ai-model-profiles.index')
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
        $user = auth()->user();
        if (!$aiModelProfile->canEditBy($user->id)) {
            abort(403, 'You do not have permission to delete this profile');
        }

        try {
            $this->service->delete($aiModelProfile, $user);
            return redirect()->route('user.ai-model-profiles.index')
                ->with('success', 'AI Model Profile deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete AI model profile', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to delete AI model profile: ' . $e->getMessage());
        }
    }

    public function clone(AiModelProfile $aiModelProfile)
    {
        try {
            $cloned = $this->service->clone($aiModelProfile, auth()->user());
            return redirect()->route('user.ai-model-profiles.edit', $cloned)
                ->with('success', 'AI Model Profile cloned successfully');
        } catch (\Exception $e) {
            Log::error('Failed to clone AI model profile', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to clone AI model profile: ' . $e->getMessage());
        }
    }
}

