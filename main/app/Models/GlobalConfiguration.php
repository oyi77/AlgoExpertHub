<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class GlobalConfiguration extends Model
{
    use HasFactory;

    protected $table = 'global_configurations';

    protected $fillable = [
        'config_key',
        'config_value',
        'description',
    ];

    protected $casts = [
        'config_value' => 'array',
    ];

    /**
     * Get configuration value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(string $key, $default = null)
    {
        try {
            if (!Schema::hasTable('global_configurations')) {
                \Log::warning('global_configurations table does not exist', ['key' => $key]);
                return $default;
            }
            $config = static::where('config_key', $key)->first();
            return $config ? $config->config_value : $default;
        } catch (\Exception $e) {
            \Log::error('GlobalConfiguration::getValue error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    /**
     * Set configuration value by key
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $description
     * @return static
     */
    public static function setValue(string $key, $value, ?string $description = null): self
    {
        try {
            if (!Schema::hasTable('global_configurations')) {
                \Log::warning('global_configurations table does not exist', ['key' => $key]);
                throw new \Exception('global_configurations table does not exist');
            }
            return static::updateOrCreate(
                ['config_key' => $key],
                [
                    'config_value' => $value,
                    'description' => $description,
                ]
            );
        } catch (\Exception $e) {
            \Log::error('GlobalConfiguration::setValue error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get decrypted value from config_value array
     * Used for sensitive fields like api_key
     *
     * @param string $field
     * @return string|null
     */
    public function getDecryptedField(string $field): ?string
    {
        $value = $this->config_value[$field] ?? null;
        
        if (empty($value)) {
            return null;
        }

        try {
            // Check if already decrypted
            if (!str_starts_with($value, 'eyJ')) {
                return $value;
            }
            
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            \Log::error("Failed to decrypt field: {$field}", [
                'config_key' => $this->config_key,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Set encrypted value in config_value array
     * Used for sensitive fields like api_key
     *
     * @param string $field
     * @param string|null $value
     * @return void
     */
    public function setEncryptedField(string $field, ?string $value): void
    {
        $configValue = $this->config_value ?? [];
        
        if (empty($value)) {
            unset($configValue[$field]);
        } else {
            $configValue[$field] = Crypt::encryptString($value);
        }
        
        $this->config_value = $configValue;
    }
}
