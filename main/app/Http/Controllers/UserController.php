<?php

namespace App\Http\Controllers;

use App\Helpers\Helper\Helper;
use App\Helpers\NotificationHelper;
use App\Http\Requests\UserProfile;
use App\Models\Payment;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Services\UserDashboardService;
use App\Services\UserProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use Inertia\Inertia;

class UserController extends Controller
{
    protected $profile, $dashboard;


    public function __construct(UserProfileService $profile, UserDashboardService $dashboard)
    {
        $this->profile = $profile;
        $this->dashboard = $dashboard;
    }

    public function dashboard()
    {
        $data = $this->dashboard->dashboard();

        $data['title'] = "Dashboard";

        // Add onboarding checklist data
        $onboardingService = app(\App\Services\UserOnboardingService::class);
        $user = auth()->user();
        
        if ($onboardingService->shouldShowOnboarding($user)) {
            $data['onboardingChecklist'] = $onboardingService->getChecklist($user);
            $data['onboardingProgress'] = $onboardingService->getProgress($user);
        } else {
            $data['onboardingChecklist'] = [];
            $data['onboardingProgress'] = 100;
        }

        return view(Helper::themeView('user.dashboard'))->with($data);
    }

    public function betaDashboard()
    {
        $data = $this->dashboard->dashboard();

        $data['title'] = "Dashboard";

        // Add onboarding checklist data
        $onboardingService = app(\App\Services\UserOnboardingService::class);
        $user = auth()->user();
        
        if ($onboardingService->shouldShowOnboarding($user)) {
            $data['onboardingChecklist'] = $onboardingService->getChecklist($user);
            $data['onboardingProgress'] = $onboardingService->getProgress($user);
        } else {
            $data['onboardingChecklist'] = [];
            $data['onboardingProgress'] = 100;
        }

        return Inertia::render('User/Dashboard', $data);
    }

    public function profile()
    {
        $data['title'] = 'Profile Edit';

        $data['user'] = auth()->user();

        return view(Helper::themeView('user.profile'))->with($data);
    }

    public function profileUpdate(UserProfile $request)
    {

        $isSuccess = $this->profile->update($request);

        if ($isSuccess['type'] === 'success')
            return back()->with('notify', NotificationHelper::success($isSuccess['message']));
    }

    public function changePassword()
    {
        $title = 'Change Password';
        return view(Helper::themeView('user.changepassword'), compact('title'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'oldpassword' => 'required|min:6',
            'password' => 'min:6|confirmed',
        ]);

        $user = User::find(auth()->id());

        if (!Hash::check($request->oldpassword, $user->password)) {
            return redirect()->back()->with('notify', NotificationHelper::error('Old password do not match'));
        } else {
            $user->password = bcrypt($request->password);

            $user->save();

            return redirect()->back()->with('notify', NotificationHelper::success('Password Updated'));
        }
    }

    public function allInvest(Request $request)
    {
        $data['title'] = 'All Investments';

        $data['investments'] = Payment::when($request->trx, function ($item) use ($request) {
            $item->where('trx', $request->trx);
        })->when($request->date, function ($item) use ($request) {
            $item->whereDate('created_at', $request->date);
        })
            ->where('user_id', auth()->id())
            ->whereIn('status', [0, 1, 2])
            ->latest()
            ->with('plan', 'gateway')
            ->paginate(Helper::pagination());

        return view(Helper::themeView('user.invest_log'))->with($data);
    }

    public function pendingInvest(Request $request)
    {
        $data['title'] = 'Pending Investments';

        $data['investments'] = Payment::when($request->trx, function ($item) use ($request) {
            $item->where('trx', $request->trx);
        })->when($request->date, function ($item) use ($request) {
            $item->whereDate('created_at', $request->date);
        })
            ->where('user_id', auth()->id())
            ->where('status', 0)
            ->latest()
            ->with('plan', 'gateway')
            ->paginate(Helper::pagination());

        return view(Helper::themeView('user.invest_log'))->with($data);
    }

    public function interestLog(Request $request)
    {
        $data['title'] = 'Interest Log';

        $data['interestLogs'] = ReferralCommission::when($request->date, function ($item) use ($request) {
            $item->whereDate('created_at', $request->date);
        })
            ->where('commission_to', auth()->id())
            ->latest()
            ->with('whoGetTheMoney', 'whoSendTheMoney')
            ->paginate(Helper::pagination());

        return view(Helper::themeView('user.interest_log'))->with($data);
    }

    // ========== BETA METHODS ==========

    public function betaProfile()
    {
        $data['title'] = 'Profile Edit';
        $data['user'] = auth()->user();
        return Inertia::render('User/Profile', $data);
    }

    public function betaProfileUpdate(UserProfile $request)
    {
        $isSuccess = $this->profile->update($request);

        if ($isSuccess['type'] === 'success') {
            return back()->with('notify', NotificationHelper::success($isSuccess['message']));
        }
    }

    public function betaChangePassword()
    {
        $title = 'Change Password';
        return Inertia::render('User/ChangePassword', ['title' => $title]);
    }

    public function betaUpdatePassword(Request $request)
    {
        $request->validate([
            'oldpassword' => 'required|min:6',
            'password' => 'min:6|confirmed',
        ]);

        $user = User::find(auth()->id());

        if (!Hash::check($request->oldpassword, $user->password)) {
            return redirect()->back()->with('notify', NotificationHelper::error('Old password do not match'));
        } else {
            $user->password = bcrypt($request->password);
            $user->save();
            return redirect()->back()->with('notify', NotificationHelper::success('Password Updated'));
        }
    }

    public function betaDeposit()
    {
        $data['title'] = 'Deposit';
        return Inertia::render('User/Deposit', $data);
    }

    public function betaWithdraw()
    {
        $data['title'] = 'Withdraw';
        return Inertia::render('User/Withdraw', $data);
    }

    public function betaSubscription()
    {
        $data['title'] = 'My Subscription';
        $data['subscription'] = \App\Models\PlanSubscription::where('user_id', auth()->id())->where('is_current', 1)->with('plan')->first();
        return Inertia::render('User/Subscription', $data);
    }

    // ========== INVESTMENT BETA METHODS ==========

    public function betaAllInvest(Request $request)
    {
        $data['title'] = 'All Investments';

        $data['investments'] = Payment::when($request->trx, function ($item) use ($request) {
            $item->where('trx', $request->trx);
        })->when($request->date, function ($item) use ($request) {
            $item->whereDate('created_at', $request->date);
        })
            ->where('user_id', auth()->id())
            ->whereIn('status', [0, 1, 2])
            ->latest()
            ->with('plan', 'gateway')
            ->paginate(Helper::pagination());

        return Inertia::render('User/Invest', $data);
    }

    public function betaPendingInvest(Request $request)
    {
        $data['title'] = 'Pending Investments';

        $data['investments'] = Payment::when($request->trx, function ($item) use ($request) {
            $item->where('trx', $request->trx);
        })->when($request->date, function ($item) use ($request) {
            $item->whereDate('created_at', $request->date);
        })
            ->where('user_id', auth()->id())
            ->where('status', 0)
            ->latest()
            ->with('plan', 'gateway')
            ->paginate(Helper::pagination());

        return Inertia::render('User/Invest', $data);
    }

    public function betaInterestLog(Request $request)
    {
        $data['title'] = 'Interest Log';

        $data['interestLogs'] = ReferralCommission::when($request->date, function ($item) use ($request) {
            $item->whereDate('created_at', $request->date);
        })
            ->where('commission_to', auth()->id())
            ->latest()
            ->with('whoGetTheMoney', 'whoSendTheMoney')
            ->paginate(Helper::pagination());

        return Inertia::render('User/InterestLog', $data);
    }

    // ========== ADDITIONAL BETA METHODS ==========

    public function betaExternalSignals(Request $request)
    {
        $data['title'] = 'External Signals';
        $data['multiChannelEnabled'] = \App\Support\AddonRegistry::active('multi-channel-signal-addon');
        $data['activeTab'] = $request->tab ?? 'sources';

        if ($data['multiChannelEnabled']) {
            $signalAddon = \App\addons\multi_channel_signal_addon\app\Services\MultiChannelSignalService::class;
            if (class_exists($signalAddon)) {
                $service = app($signalAddon);
                $data['sources'] = $service->getUserSources(auth()->id());
                $data['stats'] = $service->getSourceStats(auth()->id());
                $data['channels'] = $service->getUserChannels(auth()->id());
                $data['channelStats'] = $service->getChannelStats(auth()->id());
            }
        }

        return Inertia::render('User/ExternalSignals', $data);
    }

    public function betaTradingOverview(Request $request)
    {
        $data['title'] = 'Trading Overview';

        $cards = [];
        $connectionAddon = \App\Support\AddonRegistry::active('trading-management-addon');

        if ($connectionAddon) {
            $connections = \App\Models\ExecutionConnection::where('user_id', auth()->id())->where('status', 'active')->get();
            foreach ($connections as $connection) {
                $cards[] = [
                    'name' => $connection->name,
                    'type' => 'execution_connection',
                    'type_label' => 'Trading Connection',
                    'status' => $connection->status,
                    'broker' => $connection->broker,
                    'preset_name' => 'Default',
                    'open_positions' => 0,
                    'pl_today' => 0,
                    'pl_week' => 0,
                    'details_route' => route('user.execution-connections.edit', $connection->id),
                    'toggle_route' => route('user.execution-connections.toggle', $connection->id),
                ];
            }
        }

        $data['cards'] = $cards;

        return Inertia::render('User/TradingOverview', $data);
    }

    public function betaGateways(Request $request)
    {
        $data['title'] = 'Payment Gateways';
        $data['gateways'] = \App\Models\Gateway::where('status', 1)->get();
        $data['type'] = $request->type ?? 'deposit';
        $data['plan'] = null;

        if ($request->plan_id) {
            $data['plan'] = \App\Models\Plan::find($request->plan_id);
        }

        return Inertia::render('User/GatewayList', $data);
    }

    public function betaPaynow(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $gateway = \App\Models\Gateway::findOrFail($id);
        $type = $request->type ?? 'deposit';

        $deposit = new \App\Models\Deposit();
        $deposit->user_id = auth()->id();
        $deposit->gateway_id = $gateway->id;
        $deposit->amount = $request->amount;
        $deposit->charge = 0;
        $deposit->final_amount = $request->amount;
        $deposit->trx = str()->random(16);
        $deposit->status = 0;
        $deposit->save();

        return redirect()->route('user.gateway MANUAL', ['id' => $gateway->id, 'trx' => $deposit->trx]);
    }

    // ========== ONBOARDING BETA METHODS ==========

    public function betaOnboardingWelcome(Request $request)
    {
        $data['title'] = 'Welcome';
        $data['progress'] = 0;

        return Inertia::render('User/OnboardingWelcome', $data);
    }

    public function betaOnboardingWelcomeComplete(Request $request)
    {
        auth()->user()->update(['onboarding_completed' => false]);

        $steps = \App\Services\UserOnboardingService::STEPS;
        $firstStep = array_key_first($steps);

        return redirect()->route('user.onboarding.step', ['step' => $firstStep]);
    }

    public function betaOnboardingStep(Request $request, $step = null)
    {
        $data['title'] = 'Onboarding Setup';

        $service = app(\App\Services\UserOnboardingService::class);
        $allSteps = $service->getAllSteps();

        if (!$step || !isset($allSteps[$step])) {
            $step = array_key_first($allSteps);
        }

        $stepKeys = array_keys($allSteps);
        $currentStepIndex = array_search($step, $stepKeys);
        $totalSteps = count($stepKeys);
        $progress = round((($currentStepIndex + 1) / $totalSteps) * 100);

        $data['step'] = $step;
        $data['currentStepIndex'] = $currentStepIndex;
        $data['totalSteps'] = $totalSteps;
        $data['progress'] = $progress;
        $data['stepData'] = $service->getStepData(auth()->user(), $step);

        return Inertia::render('User/OnboardingStep', $data);
    }

    public function betaOnboardingStepComplete(Request $request, $step)
    {
        $service = app(\App\Services\UserOnboardingService::class);
        $allSteps = $service->getAllSteps();
        $stepKeys = array_keys($allSteps);
        $currentStepIndex = array_search($step, $stepKeys);
        $nextStepIndex = $currentStepIndex + 1;

        if ($nextStepIndex < count($stepKeys)) {
            $nextStep = $stepKeys[$nextStepIndex];
            return redirect()->route('user.onboarding.step', ['step' => $nextStep]);
        }

        return redirect()->route('user.onboarding.complete');
    }

    public function betaOnboardingComplete(Request $request)
    {
        $data['title'] = 'Onboarding Complete';

        $service = app(\App\Services\UserOnboardingService::class);
        $allSteps = $service->getAllSteps();

        $steps = [];
        foreach ($allSteps as $key => $label) {
            $steps[$key] = [
                'label' => $label,
                'completed' => true,
            ];
        }

        $data['steps'] = $steps;

        return Inertia::render('User/OnboardingComplete', $data);
    }

    public function betaOnboardingSkip(Request $request)
    {
        auth()->user()->update(['onboarding_completed' => true]);

        return redirect()->route('user.beta.dashboard');
    }
}
