<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Override in child classes as needed
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        Log::warning('Form validation failed', [
            'request' => static::class,
            'errors' => $validator->errors()->toArray(),
            'input' => $this->except(['password', 'password_confirmation'])
        ]);

        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'type' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'email' => 'The :attribute must be a valid email address.',
            'unique' => 'The :attribute has already been taken.',
            'min' => 'The :attribute must be at least :min characters.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'confirmed' => 'The :attribute confirmation does not match.',
            'numeric' => 'The :attribute must be a number.',
            'integer' => 'The :attribute must be an integer.',
            'boolean' => 'The :attribute field must be true or false.',
            'array' => 'The :attribute must be an array.',
            'string' => 'The :attribute must be a string.',
            'date' => 'The :attribute is not a valid date.',
            'exists' => 'The selected :attribute is invalid.',
            'in' => 'The selected :attribute is invalid.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => 'email address',
            'password' => 'password',
            'password_confirmation' => 'password confirmation',
            'first_name' => 'first name',
            'last_name' => 'last name',
            'phone' => 'phone number',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize input data
        $this->sanitizeInput();
        
        // Trim string values
        $this->trimStrings();
    }

    /**
     * Sanitize input data to prevent XSS
     */
    protected function sanitizeInput(): void
    {
        $input = $this->all();
        
        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                $value = strip_tags($value);
            }
        });
        
        $this->replace($input);
    }

    /**
     * Trim string values
     */
    protected function trimStrings(): void
    {
        $input = $this->all();
        
        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                $value = trim($value);
            }
        });
        
        $this->replace($input);
    }

    /**
     * Get validated data with only allowed fields
     */
    public function getValidatedData(array $allowedFields = []): array
    {
        $validated = $this->validated();
        
        if (empty($allowedFields)) {
            return $validated;
        }
        
        return array_intersect_key($validated, array_flip($allowedFields));
    }

    /**
     * Check if request is for API
     */
    protected function isApiRequest(): bool
    {
        return $this->is('api/*') || $this->expectsJson();
    }
}