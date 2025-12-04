<?php

namespace Addons\TradingManagement\Modules\AiAnalysis\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile;
use Illuminate\Http\Request;

class AiModelProfileController extends Controller
{
    public function index()
    {
        $profiles = AiModelProfile::with('owner')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('trading-management::backend.trading-management.strategy.ai-models.index', compact('profiles'));
    }

    public function create()
    {
        return view('trading-management::backend.trading-management.strategy.ai-models.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ai_connection_id' => 'required|exists:ai_connections,id',
            'prompt_template' => 'required|string',
            'min_confidence_required' => 'nullable|numeric|min:0|max:100',
            'enabled' => 'boolean',
        ]);

        AiModelProfile::create($validated);

        return redirect()->route('admin.trading-management.strategy.ai-models.index')
            ->with('success', 'AI Model Profile created successfully');
    }

    public function edit(AiModelProfile $aiModelProfile)
    {
        return view('trading-management::backend.trading-management.strategy.ai-models.edit', [
            'profile' => $aiModelProfile,
        ]);
    }

    public function update(Request $request, AiModelProfile $aiModelProfile)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ai_connection_id' => 'required|exists:ai_connections,id',
            'prompt_template' => 'required|string',
            'min_confidence_required' => 'nullable|numeric',
            'enabled' => 'boolean',
        ]);

        $aiModelProfile->update($validated);

        return redirect()->route('admin.trading-management.strategy.ai-models.index')
            ->with('success', 'AI Model Profile updated successfully');
    }

    public function destroy(AiModelProfile $aiModelProfile)
    {
        $aiModelProfile->delete();

        return redirect()->route('admin.trading-management.strategy.ai-models.index')
            ->with('success', 'AI Model Profile deleted successfully');
    }
}

