<?php

namespace App\Http\Controllers\Backend\Auth;


use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminResetPasswordRequest;
use App\Models\AdminPasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;
use App\Services\AdminForgotPasswordService;

class ResetPasswordController extends Controller
{

    public $redirectTo = '/admin/home';

    protected $password;

    public function __construct(AdminForgotPasswordService $password)
    {
        $this->password = $password;

        $this->middleware('admin.guest');
    }

    public function showResetForm(Request $request, $token)
    {
        $title = "Account Recovery";

        $resetToken = AdminPasswordReset::where('token', $token)->where('status', 0)->first();

        if (!$resetToken) {

            return redirect()->route('admin.password.reset')->with('notify', NotificationHelper::error('Token not found!', 'Error'));
        }
        $email = $resetToken->email;

        return view('backend.auth.reset', compact('title', 'email', 'token'));
    }


    public function sendAgain()
    {
        $isSuccess = $this->password->sendAgain();

        if ($isSuccess['type'] === 'error') {
            return back()->with('notify', NotificationHelper::error($isSuccess['message'], 'Error'));
        }

        return back()->with('notify', NotificationHelper::success($isSuccess['message'], 'Success'));
    }


    public function reset(AdminResetPasswordRequest $request)
    {

        $isSuccess = $this->password->reset($request);

        if ($isSuccess['type'] === 'error') {
            return redirect()->route('admin.login')->with('notify', NotificationHelper::error($isSuccess['message'], 'Error'));
        }


        return redirect()->route('admin.login')->with('notify', NotificationHelper::success($isSuccess['message'], 'Success'));
    }


    public function broker()
    {
        return Password::broker('admins');
    }

    protected function guard()
    {
        return Auth::guard('admin');
    }
}
