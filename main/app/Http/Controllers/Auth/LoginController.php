<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\Helper\Helper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserLoginRequest;
use App\Services\UserLogin;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    protected $login;

    public function __construct(UserLogin $login)
    {
        $this->login = $login;
    }
    public function index()
    {
        $data['title'] = 'Login Page';

        $data['content'] = Helper::builder('auth') ?? (object)['content' => (object)['title' => 'Welcome Back', 'image_one' => null]];

        return view(Helper::themeView('auth.login'))->with($data);
    }

    public function login(UserLoginRequest $request)
    {

        $isSuccess = $this->login->login($request);

        if ($isSuccess['type'] == 'error') {
            return redirect()->route('user.login')->with('notify', NotificationHelper::error($isSuccess['message'], 'Error'));
        }

        return redirect()->route('user.dashboard')->with('notify', NotificationHelper::success($isSuccess['message'], 'Success'));
    }

    public function signOut()
    {
        Auth::logout();

        return Redirect()->route('user.login');
    }
}
