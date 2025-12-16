<?php

namespace App\Http\Requests;

class UserRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins can create/update users via this request
        return auth()->guard('admin')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = $this->route('user');
        
        $rules = [
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|boolean',
            'kyc_status' => 'nullable|in:unverified,pending,approved,rejected',
            'balance' => 'nullable|numeric|min:0',
            'ref_id' => 'nullable|integer|exists:users,id',
            'telegram_chat_id' => 'nullable|string|max:255',
            'address' => 'nullable|array',
            'address.country' => 'nullable|string|max:255',
            'address.city' => 'nullable|string|max:255',
            'address.state' => 'nullable|string|max:255',
            'address.zip' => 'nullable|string|max:20',
            'address.street' => 'nullable|string|max:255'
        ];

        // Modify rules for updates
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            // Make fields optional for updates
            $rules['username'] = 'sometimes|required|string|max:255|unique:users,username,' . $userId;
            $rules['email'] = 'sometimes|required|email|max:255|unique:users,email,' . $userId;
            $rules['password'] = 'sometimes|required|string|min:8|confirmed';
            $rules['status'] = 'sometimes|required|boolean';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'username.required' => 'The username is required.',
            'username.unique' => 'This username is already taken.',
            'email.unique' => 'This email address is already registered.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'status.boolean' => 'The status must be active or inactive.',
            'kyc_status.in' => 'The KYC status must be unverified, pending, approved, or rejected.',
            'balance.numeric' => 'The balance must be a valid number.',
            'balance.min' => 'The balance cannot be negative.',
            'ref_id.exists' => 'The selected referrer does not exist.',
            'address.array' => 'The address must be a valid format.'
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'ref_id' => 'referrer',
            'telegram_chat_id' => 'Telegram chat ID',
            'kyc_status' => 'KYC status',
            'address.country' => 'country',
            'address.city' => 'city',
            'address.state' => 'state',
            'address.zip' => 'ZIP code',
            'address.street' => 'street address'
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Prevent self-referral
            if ($this->ref_id && $this->route('user') && $this->ref_id == $this->route('user')) {
                $validator->errors()->add('ref_id', 'A user cannot refer themselves.');
            }

            // Validate phone format if provided
            if ($this->phone && !preg_match('/^[\+]?[1-9][\d]{0,15}$/', $this->phone)) {
                $validator->errors()->add('phone', 'The phone number format is invalid.');
            }
        });
    }

    /**
     * Get the validated data for user creation/update
     */
    public function getUserData(): array
    {
        $data = $this->getValidatedData([
            'username',
            'email',
            'first_name',
            'last_name',
            'phone',
            'status',
            'kyc_status',
            'balance',
            'ref_id',
            'telegram_chat_id',
            'address'
        ]);

        // Hash password if provided
        if ($this->has('password')) {
            $data['password'] = bcrypt($this->password);
        }

        return $data;
    }
}