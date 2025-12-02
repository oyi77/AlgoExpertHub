<?php

namespace Addons\TradingBotSignalAddon\App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FirebaseAuthService
{
    protected $accessToken;
    protected $idToken;
    protected $refreshToken;
    protected $expiresAt;
    protected $userId;
    protected $projectId;
    protected $apiKey;

    public function __construct()
    {
        $config = config('trading-bot');
        $this->apiKey = $config['firebase']['api_key'] ?? env('FIREBASE_API_KEY');
        $this->loadTokens();
    }

    /**
     * Load tokens from config/env
     */
    protected function loadTokens(): void
    {
        // Load from config (which has defaults)
        $config = config('trading-bot');
        $tokensJson = $config['firebase']['auth_tokens_json'] ?? null;
        
        if ($tokensJson) {
            // If it's a JSON string, decode it
            if (is_string($tokensJson)) {
                $tokens = json_decode($tokensJson, true);
            } else {
                $tokens = $tokensJson;
            }
        } else {
            // Try individual env vars as fallback
            $tokens = [
                'access_token' => env('FIREBASE_ACCESS_TOKEN'),
                'id_token' => env('FIREBASE_ID_TOKEN'),
                'refresh_token' => env('FIREBASE_REFRESH_TOKEN'),
                'expires_at' => env('FIREBASE_EXPIRES_AT'),
                'user_id' => env('FIREBASE_USER_ID'),
                'project_id' => env('FIREBASE_PROJECT_ID'),
            ];
        }

        if (!empty($tokens['access_token'])) {
            $this->accessToken = $tokens['access_token'];
            $this->idToken = $tokens['id_token'] ?? null;
            $this->refreshToken = $tokens['refresh_token'] ?? null;
            $this->expiresAt = $tokens['expires_at'] ?? null;
            $this->userId = $tokens['user_id'] ?? null;
            $this->projectId = $tokens['project_id'] ?? '727577331880';
        }
    }

    /**
     * Get valid access token (refresh if needed)
     */
    public function getAccessToken(): ?string
    {
        // Check if token is expired or will expire soon
        if ($this->expiresAt) {
            $currentTime = time();
            $expiryTime = is_numeric($this->expiresAt) ? $this->expiresAt : strtotime($this->expiresAt);
            
            // Refresh if expired or will expire in 5 minutes
            if ($currentTime >= ($expiryTime - 300)) {
                Log::info('Token expired or expiring soon, refreshing...', [
                    'current' => $currentTime,
                    'expires' => $expiryTime,
                    'expired' => $currentTime >= $expiryTime
                ]);
                $this->refreshAccessToken();
            }
        }

        return $this->accessToken;
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshAccessToken(): bool
    {
        if (!$this->refreshToken || !$this->apiKey) {
            Log::warning('Cannot refresh token: missing refresh_token or api_key', [
                'has_refresh_token' => !empty($this->refreshToken),
                'has_api_key' => !empty($this->apiKey)
            ]);
            return false;
        }

        try {
            // Firebase token refresh endpoint requires API key as query parameter
            $url = 'https://securetoken.googleapis.com/v1/token?key=' . urlencode($this->apiKey);
            
            $response = Http::asForm()->post($url, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refreshToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->accessToken = $data['access_token'] ?? $this->accessToken;
                $this->idToken = $data['id_token'] ?? $this->idToken;
                
                // Calculate expiry time
                if (isset($data['expires_in'])) {
                    $this->expiresAt = time() + (int)$data['expires_in'];
                } elseif (isset($data['expires_at'])) {
                    $this->expiresAt = is_numeric($data['expires_at']) ? $data['expires_at'] : strtotime($data['expires_at']);
                } else {
                    $this->expiresAt = time() + 3600; // Default 1 hour
                }
                
                // Update refresh token if provided
                if (!empty($data['refresh_token'])) {
                    $this->refreshToken = $data['refresh_token'];
                }

                Log::info('Firebase access token refreshed successfully', [
                    'expires_at' => $this->expiresAt,
                    'expires_in_seconds' => $this->expiresAt - time()
                ]);
                return true;
            } else {
                Log::error('Failed to refresh Firebase token', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error refreshing Firebase token: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Set tokens manually
     */
    public function setTokens(array $tokens): void
    {
        $this->accessToken = $tokens['access_token'] ?? null;
        $this->idToken = $tokens['id_token'] ?? null;
        $this->refreshToken = $tokens['refresh_token'] ?? null;
        $this->expiresAt = $tokens['expires_at'] ?? null;
        $this->userId = $tokens['user_id'] ?? null;
        $this->projectId = $tokens['project_id'] ?? $this->projectId;
    }

    /**
     * Get authentication headers for API requests
     */
    public function getAuthHeaders(): array
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            return [];
        }

        return [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Check if authenticated
     */
    public function isAuthenticated(): bool
    {
        return !empty($this->accessToken);
    }
}

