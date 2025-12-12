<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\UserRegistration;

class RegistrationController extends Controller
{
    protected $register; 

    public function __construct(UserRegistration $register)
    {
       $this->register = $register;
    }
    public function index()
    {
        $data['title'] = 'Register User';

        $data['content'] = Helper::builder('auth') ?? (object)['content' => (object)['title' => 'Create Account', 'image_one' => null]];

        return view(Helper::themeView('auth.register'))->with($data);
    }

    public function register(RegisterRequest $request)
    {

        $isSuccess = $this->register->register($request);

        if($isSuccess['type'] === 'error'){
            return redirect()->back()->with('error', $isSuccess['message']);
        }

        return redirect()->route('user.dashboard')->with('success', $isSuccess['message']);

    }
}
