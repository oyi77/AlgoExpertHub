<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\UserOnboardingProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Onboarding
 *
 * Endpoints for user onboarding flow.
 */
class OnboardingApiController extends Controller
{
    /**
     * Get Onboarding Status
     *
     * Retrieve user's onboarding progress.
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "completed": false,
     *     "current_step": 1,
     *     "steps_completed": {...}
     *   }
     * }
     */
    public function status()
    {
        $user = Auth::user();
        $progress = UserOnboardingProgress::firstOrCreate(
            ['user_id' => $user->id],
            [
                'completed' => false,
                'current_step' => 1,
                'steps_completed' => []
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'completed' => $progress->completed,
                'current_step' => $progress->current_step,
                'steps_completed' => $progress->steps_completed ?? [],
                'skipped' => $progress->skipped ?? false
            ]
        ]);
    }

    /**
     * Complete Onboarding Step
     *
     * Mark a step as completed and move to next.
     *
     * @bodyParam step int required Step number. Example: 1
     * @bodyParam data object Optional step data.
     * @response 200 {
     *   "success": true,
     *   "message": "Step completed successfully"
     * }
     */
    public function completeStep(Request $request)
    {
        $request->validate([
            'step' => 'required|integer|min:1',
            'data' => 'nullable|array'
        ]);

        $user = Auth::user();
        $progress = UserOnboardingProgress::firstOrCreate(
            ['user_id' => $user->id],
            [
                'completed' => false,
                'current_step' => 1,
                'steps_completed' => []
            ]
        );

        $stepsCompleted = $progress->steps_completed ?? [];
        $stepsCompleted[$request->step] = $request->data ?? true;

        $progress->steps_completed = $stepsCompleted;
        $progress->current_step = $request->step + 1;
        $progress->save();

        return response()->json([
            'success' => true,
            'message' => 'Step completed successfully',
            'data' => [
                'current_step' => $progress->current_step,
                'steps_completed' => $progress->steps_completed
            ]
        ]);
    }

    /**
     * Skip Onboarding
     *
     * Skip the onboarding process.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Onboarding skipped"
     * }
     */
    public function skip()
    {
        $user = Auth::user();
        $progress = UserOnboardingProgress::firstOrCreate(
            ['user_id' => $user->id]
        );

        $progress->skipped = true;
        $progress->completed = true;
        $progress->save();

        return response()->json([
            'success' => true,
            'message' => 'Onboarding skipped'
        ]);
    }

    /**
     * Complete Onboarding
     *
     * Mark onboarding as fully completed.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Onboarding completed successfully"
     * }
     */
    public function complete()
    {
        $user = Auth::user();
        $progress = UserOnboardingProgress::firstOrCreate(
            ['user_id' => $user->id]
        );

        $progress->completed = true;
        $progress->save();

        return response()->json([
            'success' => true,
            'message' => 'Onboarding completed successfully'
        ]);
    }
}
