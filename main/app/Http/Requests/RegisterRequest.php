<?php

namespace App\Http\Requests;

use App\Models\Configuration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    protected $errorBag = 'registration';

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $general = Configuration::first();

        return [
            'reffered_by' => 'sometimes',
            'username' => 'required|unique:users',
            'phone' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
            'terms' => 'required|accepted',
            'g-recaptcha-response' => Rule::requiredIf(optional($general)->allow_recaptcha == 1)
        ];
    }

    public function messages()
    {
        return [
            'g-recaptcha-response.required' => 'You Have To fill recaptcha',
            'terms.required' => 'You must accept the Terms of Service and Privacy Policy'
        ];
    }
}
