<?php

namespace App\Http\Requests;

class PlanRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins can create/update plans
        return auth()->guard('admin')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $planId = $this->route('plan');
        
        $rules = [
            'name' => 'required|string|max:255|unique:plans,name',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'plan_type' => 'required|in:limited,lifetime',
            'duration' => 'nullable|integer|min:1',
            'status' => 'required|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'telegram' => 'nullable|boolean',
            'email' => 'nullable|boolean',
            'sms' => 'nullable|boolean',
            'whatsapp' => 'nullable|boolean',
            'signals' => 'nullable|array',
            'signals.*' => 'integer|exists:signals,id'
        ];

        // Modify rules for updates
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['name'] = 'sometimes|required|string|max:255|unique:plans,name,' . $planId;
            $rules['price'] = 'sometimes|required|numeric|min:0';
            $rules['plan_type'] = 'sometimes|required|in:limited,lifetime';
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
            'name.required' => 'The plan name is required.',
            'name.unique' => 'A plan with this name already exists.',
            'price.required' => 'The plan price is required.',
            'price.numeric' => 'The price must be a valid number.',
            'price.min' => 'The price cannot be negative.',
            'plan_type.required' => 'Please select a plan type.',
            'plan_type.in' => 'The plan type must be either limited or lifetime.',
            'duration.integer' => 'The duration must be a whole number.',
            'duration.min' => 'The duration must be at least 1 day.',
            'status.boolean' => 'The status must be active or inactive.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif.',
            'image.max' => 'The image may not be greater than 2MB.',
            'signals.array' => 'Signals must be an array.',
            'signals.*.exists' => 'One or more selected signals are invalid.'
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'plan_type' => 'plan type',
            'signals' => 'signals'
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Duration is required for limited plans
            if ($this->plan_type === 'limited' && empty($this->duration)) {
                $validator->errors()->add('duration', 'Duration is required for limited plans.');
            }

            // Duration should not be set for lifetime plans
            if ($this->plan_type === 'lifetime' && !empty($this->duration)) {
                $validator->errors()->add('duration', 'Duration should not be set for lifetime plans.');
            }

            // Validate price is reasonable
            if ($this->price && $this->price > 10000) {
                $validator->errors()->add('price', 'Price seems unusually high. Please verify.');
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        
        // Convert checkbox values to boolean
        $booleanFields = ['status', 'telegram', 'email', 'sms', 'whatsapp'];
        
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN)
                ]);
            }
        }
        
        // Set default notification preferences if not provided
        if (!$this->has('telegram')) {
            $this->merge(['telegram' => true]);
        }
        
        if (!$this->has('email')) {
            $this->merge(['email' => true]);
        }
    }

    /**
     * Get the validated data for plan creation/update
     */
    public function getPlanData(): array
    {
        return $this->getValidatedData([
            'name',
            'description',
            'price',
            'plan_type',
            'duration',
            'status',
            'image',
            'telegram',
            'email',
            'sms',
            'whatsapp'
        ]);
    }

    /**
     * Get the selected signal IDs
     */
    public function getSignalIds(): array
    {
        return $this->validated()['signals'] ?? [];
    }
}