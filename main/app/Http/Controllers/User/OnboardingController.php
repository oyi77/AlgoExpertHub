<?php

namespace App\Http\Controllers\User;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Services\UserOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    protected $onboardingService;

    public function __construct(UserOnboardingService $onboardingService)
    {
        $this->onboardingService = $onboardingService;
    }

    /**
     * Show welcome screen
     */
    public function welcome()
    {
        $user = Auth::user();
        $data['title'] = __('Welcome');
        $data['progress'] = $this->onboardingService->getProgress($user);
        $data['steps'] = $this->onboardingService->getSteps($user);

        return view(Helper::theme() . 'user.onboarding.welcome')->with($data);
    }

    /**
     * Complete welcome step
     */
    public function completeWelcome(Request $request)
    {
        $user = Auth::user();
        $this->onboardingService->completeStep($user, 'welcome');

        // Get next step
        $steps = $this->onboardingService->getSteps($user);
        $nextStep = $this->getNextStep($steps, 'welcome');

        if ($nextStep) {
            return redirect()->route('user.onboarding.step', ['step' => $nextStep]);
        }

        // All steps complete
        return redirect()->route('user.onboarding.complete');
    }

    /**
     * Show specific onboarding step
     */
    public function step(string $step)
    {
        $user = Auth::user();
        $steps = $this->onboardingService->getSteps($user);

        if (!isset($steps[$step])) {
            abort(404, __('Onboarding step not found'));
        }

        $data['title'] = __('Onboarding');
        $data['step'] = $step;
        $data['stepData'] = $steps[$step];
        $data['progress'] = $this->onboardingService->getProgress($user);
        $data['allSteps'] = $steps;
        $data['currentStepIndex'] = array_search($step, array_keys($steps));
        $data['totalSteps'] = count($steps);

        return view(Helper::theme() . 'user.onboarding.step')->with($data);
    }

    /**
     * Complete a specific step
     */
    public function completeStep(Request $request, string $step)
    {
        $user = Auth::user();
        $this->onboardingService->completeStep($user, $step);

        // Auto-sync progress from current state
        $this->onboardingService->syncProgress($user);

        // Get next step
        $steps = $this->onboardingService->getSteps($user);
        $nextStep = $this->getNextStep($steps, $step);

        if ($nextStep) {
            return redirect()->route('user.onboarding.step', ['step' => $nextStep])
                ->with('success', __('Step completed successfully'));
        }

        // All steps complete
        return redirect()->route('user.onboarding.complete');
    }

    /**
     * Skip entire onboarding
     */
    public function skip(Request $request)
    {
        $user = Auth::user();
        $this->onboardingService->completeOnboarding($user);

        return redirect()->route('user.dashboard')
            ->with('success', __('Onboarding skipped. You can complete it later from your dashboard.'));
    }

    /**
     * Show completion screen
     */
    public function complete()
    {
        $user = Auth::user();
        $this->onboardingService->completeOnboarding($user);

        $data['title'] = __('Onboarding Complete');
        $data['steps'] = $this->onboardingService->getSteps($user);

        return view(Helper::theme() . 'user.onboarding.complete')->with($data);
    }

    /**
     * Get next step after current step
     * 
     * @param array $steps
     * @param string $currentStep
     * @return string|null
     */
    private function getNextStep(array $steps, string $currentStep): ?string
    {
        $stepKeys = array_keys($steps);
        $currentIndex = array_search($currentStep, $stepKeys);

        if ($currentIndex === false || $currentIndex === count($stepKeys) - 1) {
            return null;
        }

        // Find next required step or next step
        for ($i = $currentIndex + 1; $i < count($stepKeys); $i++) {
            $nextStepKey = $stepKeys[$i];
            $nextStep = $steps[$nextStepKey];

            // Check if step should be shown (conditional steps)
            if (isset($nextStep['condition'])) {
                if (!\App\Support\AddonRegistry::active($nextStep['condition'])) {
                    continue;
                }
            }

            return $nextStepKey;
        }

        return null;
    }
}
