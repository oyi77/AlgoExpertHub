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
}
