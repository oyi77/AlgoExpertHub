<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class AdminUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $userId = request()->user ?? request()->route('id') ?? request()->route('user');
        $user = $userId ? User::find($userId) : null;

        $rules = [];

        if (request()->has('phone')) {
            $rules['phone'] = $user 
                ? 'unique:users,phone,' . $user->id
                : 'unique:users,phone';
        }

        return $rules;
    }
}
