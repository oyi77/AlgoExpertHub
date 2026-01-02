<?php

namespace Addons\MultiChannelSignalAddon\App\Services;

use Addons\MultiChannelSignalAddon\App;
use App\Helpers\NotificationHelper;

class AdminLoginService
{
    public function validateData($request)
    {
        $fieldType = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $data = [$fieldType => $request->email, 'password' => $request->password];

        $admin = Admin::where('email', $request->email)->orWhere('username', $request->email)->first();

        if ($admin) {
            if (!$admin->status) {
                return redirect()->route('admin.login')->with('notify', NotificationHelper::error('Your account is currently disabled', 'Error'));
            }
        }

        $remember = $request->remember == 'on' ? true : false;

        return [$data, $remember];
    }
}
