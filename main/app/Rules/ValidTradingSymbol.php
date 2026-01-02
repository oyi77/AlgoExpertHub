<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTradingSymbol implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Basic format validation
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');
            return;
        }

        // Length check
        if (strlen($value) < 3 || strlen($value) > 20) {
            $fail('The :attribute must be between 3 and 20 characters.');
            return;
        }

        // Allow only alphanumeric characters, forward slash, and underscore
        // Examples: EURUSD, BTC/USDT, EUR/USD, BTC_USDT
        if (!preg_match('/^[A-Z0-9\/\_]+$/i', $value)) {
            $fail('The :attribute contains invalid characters. Only letters, numbers, slash, and underscore are allowed.');
            return;
        }

        // Common symbol patterns
        $patterns = [
            '/^[A-Z]{6}$/i',           // EURUSD, GBPUSD (forex pairs)
            '/^[A-Z]{3}\/[A-Z]{3,4}$/i', // BTC/USD, BTC/USDT (crypto with slash)
            '/^[A-Z]{3,4}[A-Z]{3,4}$/i', // BTCUSDT (crypto without slash)
            '/^[A-Z]{2,5}\_[A-Z]{2,5}$/i', // BTC_USDT (crypto with underscore)
        ];

        $isValid = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            $fail('The :attribute does not match any recognized trading symbol format.');
        }
    }
}
