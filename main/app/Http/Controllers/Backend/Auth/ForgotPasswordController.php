<?php

namespace App\Http\Controllers\Backend\Auth;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminForgotPasswordRequest;
use App\Models\Admin;
use App\Models\AdminPasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use App\Models\GeneralSetting;
use App\Services\AdminForgotPasswordService;

class ForgotPasswordController extends Controller
{

    protected $forgotPassword;

    public function __construct(AdminForgotPasswordService $forgotPassword)
    {

        $this->forgotPassword = $forgotPassword;

        $this->middleware('admin.guest');
    }

    public function showLinkRequestForm()
    {
        $data['title'] = 'Account Recovery';

        AdminPasswordReset::truncate();

        return view('backend.auth.forgot-password')->with($data);
    }

    public function broker()
    {
        return Password::broker('admins');
    }

    public function sendResetCodeEmail(AdminForgotPasswordRequest $request)
    {
        $isFired = $this->forgotPassword->forgot($request);

        if($isFired['type'] === 'error'){
            return back()->with('notify', NotificationHelper::error($isFired['message'], 'Error'));
        }

        return redirect()->route('admin.password.verify.code')->with('notify', NotificationHelper::success($isFired['message'], 'Success'));
    }

    public function verifyCodeForm(Request $request)
    {
        $data['title'] = __('Code Verify');

        // Check if session code exists, if not redirect back to reset form
        if (!session()->has('code')) {
            return redirect()->route('admin.password.reset')->with('notify', NotificationHelper::error('Session expired. Please request a new verification code.', 'Error'));
        }

        return view('backend.auth.code_verify')->with($data);
    }


    public function verifyCode(AdminForgotPasswordRequest $request)
    {
        try {
            $sessionCode = session('code');
            
            // Check if session code exists
            if (!$sessionCode) {
                return redirect()->route('admin.password.reset')->with('notify', NotificationHelper::error('Session expired. Please request a new verification code.', 'Error'));
            }

            // Verify code from session
            if ($sessionCode == $request->code) {
                // Also verify in database as additional check
                $resetToken = AdminPasswordReset::where('token', $request->code)
                    ->where('status', 0)
                    ->first();

                if (!$resetToken) {
                    return back()->with('notify', NotificationHelper::error('Invalid or expired verification code. Please request a new code.', 'Error'));
                }

                return redirect()->route('admin.password.reset.form', $request->code)->with('notify', NotificationHelper::success('Now you can reset your Password', 'Success'));
            }

            return back()->with('notify', NotificationHelper::error('Verification Code did not match', 'Error'));
        } catch (\Exception $e) {
            \Log::error('Password verification error: ' . $e->getMessage());
            return back()->with('notify', NotificationHelper::error('An error occurred. Please try again.', 'Error'));
        }
    }
}
