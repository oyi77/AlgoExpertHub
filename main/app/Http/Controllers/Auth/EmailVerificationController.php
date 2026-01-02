<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\Helper\Helper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmailVerificationRequest;
use App\Services\EmailVerification;


class EmailVerificationController extends Controller
{
    protected $verify;

    public function __construct(EmailVerification $verifcation)
    {
        $this->verify = $verifcation;
    }

    public function emailVerify()
    {
        $data['title'] = "Email Verify";

        return view(Helper::themeView('auth.email_sms_verify'));
    }

    public function emailVerifyConfirm(EmailVerificationRequest $request)
    {
        $isSucces = $this->verify->verify($request);

        if ($isSucces['type'] === 'success') {
            return  redirect()->route('user.dashboard')->with('notify', NotificationHelper::success($isSucces['message'], 'Success'));
        }

        return  redirect()->back()->with('notify', NotificationHelper::error($isSucces['message'], 'Error'));
    }
}
