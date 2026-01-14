<?php

namespace App\Services;

use App\Models\Admin;

class AdminLoginService
{
    public function validateData($request)
    {
        $fieldType = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $data = [$fieldType => $request->email, 'password' => $request->password];

        $remember = $request->remember == 'on' ? true : false;

        return [$data, $remember];
    }
    
    public function checkAdminStatus($email)
    {
        $admin = Admin::where('email', $email)->orWhere('username', $email)->first();

        if ($admin && !$admin->status) {
            return false;
        }

        return true;
    }
}
