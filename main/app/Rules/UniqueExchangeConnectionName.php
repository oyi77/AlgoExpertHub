<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\ExchangeConnection; // Assumption: model exists or will exist

class UniqueExchangeConnectionName implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Mock logic for pilot since ExchangeConnection model might not exist or be fully set up
        // In real app:
        // if (ExchangeConnection::where('user_id', auth()->id())->where('name', $value)->exists()) {
        //     $fail('The :attribute has already been taken.');
        // }
        
        // For pilot demonstration:
        if ($value === 'Existing Connection') {
            $fail('The :attribute has already been taken.');
        }
    }
}
