<?php

namespace Addons\TradingManagement\Modules\AiAnalysis\Controllers\User;

use App\Http\Controllers\Controller;
use App\Helpers\NotificationHelper;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AiModelProfileController extends Controller
{
    /**
     * Display a listing of the user's AI model profiles
     */
    public function index()
    {
        try {
            $title = 'My AI Model Profiles';
            
            if (!Schema::hasTable('ai_model_profiles')) {
                Log::warning('AI model profiles table does not exist');
                return view('trading-management::user.ai-analysis.profiles.index', [
                    'profiles' => collect([])->paginate(20),
                    'title' => $title
                ]);
            }
            
            $profiles = AiModelProfile::where('created_by_user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate(20);
            return view('trading-management::user.ai-analysis.profiles.index', compact('profiles', 'title'));
        } catch (\Exception $e) {
            Log::error('AI model profiles index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return view('trading-management::user.ai-analysis.profiles.index', [
                'profiles' => collect([])->paginate(20),
                'title' => 'My AI Model Profiles'
            ]);
        }
    }

    /**
     * Display marketplace AI model profiles
     */
    public function marketplace()
    {
        try {
            $title = 'AI Model Profiles Marketplace';
            
            if (!Schema::hasTable('ai_model_profiles')) {
                Log::warning('AI model profiles table does not exist');
                return view('trading-management::user.ai-analysis.profiles.marketplace', [
                    'profiles' => collect([])->paginate(20),
                    'title' => $title
                ]);
            }
            
            $profiles = AiModelProfile::whereNull('created_by_user_id')
                ->where('visibility', 'PUBLIC_MARKETPLACE')
                ->orderBy('created_at', 'desc')
                ->paginate(20);
            return view('trading-management::user.ai-analysis.profiles.marketplace', compact('profiles', 'title'));
        } catch (\Exception $e) {
            Log::error('AI model profiles marketplace error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return view('trading-management::user.ai-analysis.profiles.marketplace', [
                'profiles' => collect([])->paginate(20),
                'title' => 'AI Model Profiles Marketplace'
            ]);
        }
    }

    /**
     * Show the form for creating a new AI model profile
     */
    public function create()
    {
        $title = 'Create AI Model Profile';
        return view('trading-management::user.ai-analysis.profiles.create', compact('title'));
    }

    /**
     * Clone an AI model profile for the authenticated user
     */
    public function clone($id)
    {
        try {
            $profile = AiModelProfile::findOrFail($id);
            
            if (!$profile->canBeClonedBy(auth()->id())) {
                return back()->with('notify', NotificationHelper::error(__('This AI profile cannot be cloned.'), 'Error'));
            }
            
            $clonedProfile = $profile->replicate();
            $clonedProfile->created_by_user_id = auth()->id();
            $clonedProfile->visibility = 'PRIVATE';
            $clonedProfile->name = $profile->name . ' (Copy)';
            $clonedProfile->save();
            
            return back()->with('notify', NotificationHelper::success(__('AI profile cloned successfully!'), 'Success'));
        } catch (\Exception $e) {
            Log::error('AI model profile clone error: ' . $e->getMessage());
            return back()->with('notify', NotificationHelper::error(__('Failed to clone AI profile. Please try again.'), 'Error'));
        }
    }
}
