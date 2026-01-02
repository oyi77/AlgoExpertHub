<?php

namespace App\Http\Controllers\Backend\Auth;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Models\Admin;
use App\Services\AdminLoginService;

class LoginController extends Controller
{

    protected $login;

    public function __construct(AdminLoginService $login)
    {
        $this->login = $login;

        $this->middleware('admin.guest')->except('logout');

    }

    public function loginPage()
    {
        $data['title'] = __('Admin Login Page');

        return view('backend.auth.login')->with($data);
    }


    public function login(AdminLoginRequest $request)
    {
        
        [$data, $remember] = $this->login->validateData($request);

        if(auth()->guard('admin')->attempt($data, $remember)){
            return redirect()->route('admin.home')->with('notify', NotificationHelper::success('Login Successful', 'Success'));
        }

        return redirect()->route('admin.login')->with('notify', NotificationHelper::error('Invalid Credentials', 'Error'));
    }

    public function logout()
    {
        auth()->guard('admin')->logout();

        return redirect()->route('admin.login')->with('notify', NotificationHelper::success('Logout Successful', 'Success'));
    }
}
