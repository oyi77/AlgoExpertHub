<?php

namespace App\Http\Controllers;

use App\Helpers\Helper\Helper;
use App\Http\Requests\UserProfile;
use App\Models\Payment;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Services\UserDashboardService;
use App\Services\UserProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            return back()->with('success', $isSuccess['message']);
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
            return redirect()->back()->with('error', 'Old password do not match');
        } else {
            $user->password = bcrypt($request->password);

            $user->save();

            return redirect()->back()->with('success', 'Password Updated');
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
}
