<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AuditLog;
use Carbon\Carbon;

class SecurityManager
{
    /**
     * Encrypt sensitive data fields
     */
    public function encryptSensitiveData(array $data): array
    {
        $sensitiveFields = ['api_key', 'api_secret', 'password', 'token', 'secret'];
        $encrypted = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $sensitiveFields) || str_contains($key, 'secret') || str_contains($key, 'key')) {
                $encrypted[$key] = Crypt::encryptString($value);
            } else {
                $encrypted[$key] = $value;
            }
        }

        return $encrypted;
    }

    /**
     * Decrypt sensitive data fields
     */
    public function decryptSensitiveData(array $data): array
    {
        $sensitiveFields = ['api_key', 'api_secret', 'password', 'token', 'secret'];
        $decrypted = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $sensitiveFields) || str_contains($key, 'secret') || str_contains($key, 'key')) {
                try {
                    $decrypted[$key] = Crypt::decryptString($value);
                } catch (\Exception $e) {
                    $decrypted[$key] = $value;
                }
            } else {
                $decrypted[$key] = $value;
            }
        }

        return $decrypted;
    }

    /**
     * Validate API request for security
     */
    public function validateApiRequest(Request $request): bool
    {
        // Check for required headers
        if (!$request->hasHeader('X-API-Key')) {
            return false;
        }

        // Validate API key
        $apiKey = $request->header('X-API-Key');
        if (!$this->isValidApiKey($apiKey)) {
            return false;
        }

        // Check request signature if present
        if ($request->hasHeader('X-Signature')) {
            return $this->validateRequestSignature($request);
        }

        return true;
    }

    /**
     * Detect suspicious activity for a user
     */
    public function detectSuspiciousActivity(User $user): bool
    {
        $recentLogins = DB::table('audit_logs')
            ->where('user_id', $user->id)
            ->where('action', 'login')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->get();

        // Check for multiple failed login attempts
        $failedLogins = $recentLogins->where('status', 'failed')->count();
        if ($failedLogins >= 5) {
            return true;
        }

        // Check for logins from multiple locations
        $uniqueIps = $recentLogins->pluck('ip_address')->unique()->count();
        if ($uniqueIps >= 3) {
            return true;
        }

        // Check for unusual activity patterns
        $recentActions = DB::table('audit_logs')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->count();

        if ($recentActions >= 100) {
            return true;
        }

        return false;
    }

    /**
     * Generate audit log for critical actions
     */
    public function generateAuditLog(string $action, array $context): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $context['user_id'] ?? null,
            'action' => $action,
            'entity_type' => $context['entity_type'] ?? null,
            'entity_id' => $context['entity_id'] ?? null,
            'ip_address' => $context['ip_address'] ?? request()->ip(),
            'user_agent' => $context['user_agent'] ?? request()->userAgent(),
            'changes' => isset($context['changes']) ? json_encode($context['changes']) : null,
            'status' => $context['status'] ?? 'success',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Hash sensitive string
     */
    public function hashSensitiveString(string $value): string
    {
        return Hash::make($value);
    }

    /**
     * Verify hashed string
     */
    public function verifyHash(string $value, string $hash): bool
    {
        return Hash::check($value, $hash);
    }

    /**
     * Generate secure token
     */
    public function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Validate API key
     */
    protected function isValidApiKey(string $apiKey): bool
    {
        return DB::table('api_keys')
            ->where('key', $apiKey)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Validate request signature
     */
    protected function validateRequestSignature(Request $request): bool
    {
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');

        // Check timestamp is within 5 minutes
        if (abs(time() - (int)$timestamp) > 300) {
            return false;
        }

        // Get API secret for the key
        $apiKey = $request->header('X-API-Key');
        $apiSecret = DB::table('api_keys')
            ->where('key', $apiKey)
            ->value('secret');

        if (!$apiSecret) {
            return false;
        }

        // Compute expected signature
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $timestamp . $payload, $apiSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Sanitize user input
     */
    public function sanitizeInput(string $input): string
    {
        return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Check if IP is blacklisted
     */
    public function isIpBlacklisted(string $ip): bool
    {
        return DB::table('ip_blacklist')
            ->where('ip_address', $ip)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Add IP to blacklist
     */
    public function blacklistIp(string $ip, string $reason = ''): void
    {
        DB::table('ip_blacklist')->insert([
            'ip_address' => $ip,
            'reason' => $reason,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
