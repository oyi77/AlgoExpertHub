<?php

namespace Addons\TradingManagement\Shared\Helpers;

/**
 * Helper class for redacting sensitive credential data from logs and arrays
 */
class CredentialRedactionHelper
{
    /**
     * List of credential keys that should be redacted
     */
    protected static array $sensitiveKeys = [
        'api_key',
        'api_secret',
        'api_passphrase',
        'passphrase',
        'secret',
        'password',
        'token',
        'access_token',
        'refresh_token',
        'private_key',
        'private_key_id',
        'client_secret',
        'account_id', // MetaAPI account ID can be sensitive
    ];

    /**
     * Redact sensitive credentials from an array
     * 
     * @param array $data Data array that may contain credentials
     * @param array $additionalKeys Additional keys to redact (optional)
     * @return array Data with credentials redacted
     */
    public static function redact(array $data, array $additionalKeys = []): array
    {
        $keysToRedact = array_merge(self::$sensitiveKeys, $additionalKeys);
        $redacted = [];

        foreach ($data as $key => $value) {
            $keyLower = strtolower($key);
            
            // Check if key matches any sensitive pattern
            $shouldRedact = false;
            foreach ($keysToRedact as $sensitiveKey) {
                if (str_contains($keyLower, strtolower($sensitiveKey))) {
                    $shouldRedact = true;
                    break;
                }
            }

            if ($shouldRedact) {
                // Redact the value
                if (is_string($value) && !empty($value)) {
                    $redacted[$key] = self::maskValue($value);
                } elseif (is_array($value)) {
                    $redacted[$key] = self::redact($value, $additionalKeys);
                } else {
                    $redacted[$key] = '[REDACTED]';
                }
            } elseif (is_array($value)) {
                // Recursively redact nested arrays
                $redacted[$key] = self::redact($value, $additionalKeys);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }

    /**
     * Mask a sensitive value (show first 4 and last 4 chars if long enough)
     * 
     * @param string $value Value to mask
     * @return string Masked value
     */
    protected static function maskValue(string $value): string
    {
        $length = strlen($value);
        
        if ($length <= 8) {
            return str_repeat('*', $length);
        }
        
        // Show first 4 and last 4 characters
        $prefix = substr($value, 0, 4);
        $suffix = substr($value, -4);
        $masked = str_repeat('*', max(4, $length - 8));
        
        return $prefix . $masked . $suffix;
    }

    /**
     * Redact credentials from log context
     * 
     * @param array $context Log context array
     * @return array Redacted context
     */
    public static function redactLogContext(array $context): array
    {
        return self::redact($context);
    }

    /**
     * Check if an array contains credentials
     * 
     * @param array $data Data to check
     * @return bool True if credentials detected
     */
    public static function containsCredentials(array $data): bool
    {
        foreach (self::$sensitiveKeys as $key) {
            if (isset($data[$key]) || isset($data['credentials'][$key])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Remove credentials from array completely (for safe serialization)
     * 
     * @param array $data Data array
     * @param array $additionalKeys Additional keys to remove
     * @return array Data with credentials removed
     */
    public static function removeCredentials(array $data, array $additionalKeys = []): array
    {
        $keysToRemove = array_merge(self::$sensitiveKeys, $additionalKeys);
        $cleaned = [];

        foreach ($data as $key => $value) {
            $keyLower = strtolower($key);
            
            // Check if key should be removed
            $shouldRemove = false;
            foreach ($keysToRemove as $sensitiveKey) {
                if (str_contains($keyLower, strtolower($sensitiveKey))) {
                    $shouldRemove = true;
                    break;
                }
            }

            if (!$shouldRemove) {
                if (is_array($value)) {
                    $cleaned[$key] = self::removeCredentials($value, $additionalKeys);
                } else {
                    $cleaned[$key] = $value;
                }
            }
        }

        return $cleaned;
    }
}

