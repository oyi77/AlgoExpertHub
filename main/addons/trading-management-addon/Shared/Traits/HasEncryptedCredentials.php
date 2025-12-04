<?php

namespace Addons\TradingManagement\Shared\Traits;

use Illuminate\Support\Facades\Crypt;

/**
 * Trait for models that store encrypted credentials
 * 
 * Usage:
 * - Add this trait to your model
 * - Model must have a 'credentials' column (text)
 * - Automatically encrypts on save, decrypts on read
 */
trait HasEncryptedCredentials
{
    /**
     * Encrypt credentials before saving
     * 
     * @param mixed $value Credentials array or string
     * @return void
     */
    public function setCredentialsAttribute($value): void
    {
        if (is_array($value)) {
            $json = json_encode($value);
            if ($json === false) {
                \Log::error("Failed to JSON encode credentials", [
                    'model' => get_class($this),
                    'id' => $this->id ?? 'new'
                ]);
                throw new \RuntimeException("Failed to encode credentials to JSON");
            }
            $this->attributes['credentials'] = Crypt::encryptString($json);
        } elseif (is_string($value) && !empty($value)) {
            // If already a string, encrypt it
            $this->attributes['credentials'] = Crypt::encryptString($value);
        } else {
            $this->attributes['credentials'] = null;
        }
    }

    /**
     * Decrypt credentials when retrieving
     * 
     * @param mixed $value Encrypted credentials
     * @return array Decrypted credentials as array
     */
    public function getCredentialsAttribute($value): array
    {
        if (empty($value)) {
            return [];
        }

        try {
            // Check if value is already decrypted (for backward compatibility)
            if (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
            
            // Decrypt
            $decrypted = Crypt::decryptString($value);
            $decoded = json_decode($decrypted, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error("Failed to JSON decode credentials", [
                    'model' => get_class($this),
                    'id' => $this->id ?? 'unknown',
                    'json_error' => json_last_error_msg(),
                    'decrypted_length' => strlen($decrypted)
                ]);
                return [];
            }
            
            return is_array($decoded) ? $decoded : [];
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            \Log::error("Failed to decrypt credentials - DecryptException", [
                'model' => get_class($this),
                'id' => $this->id ?? 'unknown',
                'error' => $e->getMessage(),
                'value_length' => strlen($value ?? '')
            ]);
            return [];
        } catch (\Throwable $th) {
            \Log::error("Failed to decrypt credentials", [
                'model' => get_class($this),
                'id' => $this->id ?? 'unknown',
                'error' => $th->getMessage(),
                'value_length' => strlen($value ?? '')
            ]);
            return [];
        }
    }

    /**
     * Get a specific credential value
     * 
     * @param string $key Credential key
     * @param mixed $default Default value if key not found
     * @return mixed Credential value
     */
    public function getCredential(string $key, $default = null)
    {
        $credentials = $this->credentials;
        return $credentials[$key] ?? $default;
    }

    /**
     * Set a specific credential value
     * 
     * @param string $key Credential key
     * @param mixed $value Credential value
     * @return void
     */
    public function setCredential(string $key, $value): void
    {
        $credentials = $this->credentials;
        $credentials[$key] = $value;
        $this->credentials = $credentials;
    }
}

