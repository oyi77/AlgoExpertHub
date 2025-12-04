<?php

namespace Addons\TradingManagement\Modules\AiAnalysis\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile;
use Illuminate\Http\Request;

class AiModelProfileController extends Controller
{
    public function index()
    {
        $title = 'AI Model Profiles';
        $profiles = AiModelProfile::with('owner')->orderBy('created_at', 'desc')->paginate(20);
        return view('trading-management::backend.trading-management.strategy.ai-models.index', compact('title', 'profiles'));
    }

    public function create()
    {
        $title = 'Create AI Model Profile';
        return view('trading-management::backend.trading-management.strategy.ai-models.create', compact('title'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:PRIVATE,PUBLIC_MARKETPLACE',
            'mode' => 'required|in:CONFIRM,SCAN,POSITION_MGMT',
            'prompt_template' => 'required|string',
            'settings' => 'nullable|array',
        ]);

        $profile = AiModelProfile::create([
            ...$validated,
            'enabled' => true,
            'clonable' => $request->boolean('clonable'),
        ]);

        return redirect()->route('admin.trading-management.strategy.ai-models.index')
            ->with('success', 'AI model profile created successfully');
    }

    public function edit(AiModelProfile $aiModel)
    {
        $title = 'Edit AI Model Profile';
        $profile = $aiModel;
        return view('trading-management::backend.trading-management.strategy.ai-models.edit', compact('title', 'profile'));
    }

    public function update(Request $request, AiModelProfile $aiModel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:PRIVATE,PUBLIC_MARKETPLACE',
            'mode' => 'required|in:CONFIRM,SCAN,POSITION_MGMT',
            'prompt_template' => 'required|string',
            'settings' => 'nullable|array',
            'enabled' => 'sometimes|boolean',
        ]);

        $aiModel->update([
            ...$validated,
            'clonable' => $request->boolean('clonable'),
        ]);

        return redirect()->route('admin.trading-management.strategy.ai-models.index')
            ->with('success', 'AI model profile updated successfully');
    }

    public function destroy(AiModelProfile $aiModel)
    {
        $aiModel->delete();
        return redirect()->route('admin.trading-management.strategy.ai-models.index')
            ->with('success', 'AI model profile deleted successfully');
    }
}
