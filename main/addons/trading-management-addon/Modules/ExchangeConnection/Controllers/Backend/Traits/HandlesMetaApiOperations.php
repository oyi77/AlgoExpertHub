<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits;

use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\DataProvider\Services\MetaApiProvisioningService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait HandlesMetaApiOperations
{
    /**
     * Add MT account to MetaApi
     */
    public function addMetaApiAccount(Request $request)
    {
        $validated = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'server' => 'required|string',
            'name' => 'required|string|max:255',
            'platform' => 'required|in:MT4,MT5,mt4,mt5',
            'provisioning_profile_id' => 'nullable|string',
            'account_type' => 'nullable|in:cloud-g1,cloud-g2',
            'magic' => 'nullable|integer|min:0',
            'manual_trades' => 'nullable|boolean',
        ]);

        try {
            $provisioningService = new MetaApiProvisioningService();

            $result = $provisioningService->addAccount([
                'login' => $validated['login'],
                'password' => $validated['password'],
                'server' => $validated['server'],
                'name' => $validated['name'],
                'platform' => $validated['platform'],
                'provisioningProfileId' => $validated['provisioning_profile_id'] ?? null,
                'type' => $validated['account_type'] ?? 'cloud-g2',
                'magic' => $validated['magic'] ?? null,
                'manualTrades' => $validated['manual_trades'] ?? false,
            ]);

            if ($result['success']) {
                $accountId = $result['account_id'];
                
                // Check if connection already exists with this account_id
                $existingConnection = ExchangeConnection::where('provider', 'metaapi')
                    ->get()
                    ->filter(function ($conn) use ($accountId) {
                        $creds = $conn->credentials ?? [];
                        return isset($creds['account_id']) && $creds['account_id'] === $accountId;
                    })
                    ->first();

                if ($existingConnection) {
                    // Connection already exists - return existing one
                    return response()->json([
                        'success' => true,
                        'message' => 'Account already linked to existing connection',
                        'metaapi_account_id' => $accountId,
                        'connection_id' => $existingConnection->id,
                        'existing' => true,
                        'data' => $result['data'] ?? [],
                    ]);
                }

                // Create exchange connection automatically
                $connection = ExchangeConnection::create([
                    'name' => $validated['name'],
                    'connection_type' => 'FX_BROKER',
                    'type' => 'fx', // Legacy field
                    'provider' => 'metaapi',
                    'exchange_name' => 'metaapi', // Legacy field
                    'credentials' => [
                        'api_token' => config('trading-management.metaapi.api_token'),
                        'account_id' => $accountId,
                    ],
                    'data_fetching_enabled' => true,
                    'trade_execution_enabled' => true,
                    'admin_id' => auth()->guard('admin')->id(),
                    'is_admin_owned' => true,
                    'status' => 'inactive', // enum: 'active', 'inactive', 'error', 'testing'
                    'is_active' => false, // Will be activated after testing
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'metaapi_account_id' => $accountId,
                    'connection_id' => $connection->id,
                    'data' => $result['data'] ?? [],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error_data' => $result['data'] ?? null,
                    'status_code' => $result['status_code'] ?? 400,
                ], $result['status_code'] ?? 400);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to add MetaApi account', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add account: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get MetaApi account status
     */
    public function getMetaApiAccountStatus(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|string',
        ]);

        try {
            $provisioningService = new MetaApiProvisioningService();
            $result = $provisioningService->getAccountStatus($validated['account_id']);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Monitor MetaApi connection (Server-Sent Events)
     */
    public function monitorMetaApi(ExchangeConnection $exchangeConnection)
    {
        if (strtolower($exchangeConnection->provider) !== 'metaapi') {
            return response('Only MetaApi connections can be monitored', 400);
        }

        $credentials = $exchangeConnection->credentials;
        if (empty($credentials['account_id'])) {
            return response('MetaApi Account ID not found', 400);
        }

        $accountId = $credentials['account_id'];
        
        // Prefer account token if available (more secure, scoped to account)
        // Fallback to main API token: credentials -> config -> global settings
        $apiToken = $credentials['account_token'] 
            ?? $credentials['api_token']
            ?? config('trading-management.metaapi.api_token')
            ?? $this->getMetaApiTokenFromGlobalSettings();

        if (empty($apiToken)) {
            return response('MetaApi API token not configured. Please configure it in Global Settings, connection credentials, or generate an account token.', 400);
        }

        // Disable output buffering
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Disable time limit
        set_time_limit(0);
        ignore_user_abort(false);

        // Get base URL from config/global settings (same as MetaApiAdapter)
        $baseUrl = $credentials['base_url'] 
            ?? config('trading-management.metaapi.base_url')
            ?? $this->getMetaApiBaseUrlFromGlobalSettings();

        // Send initial connection message
        echo "data: " . json_encode(['type' => 'connected', 'message' => 'MetaApi monitoring connected']) . "\n\n";
        flush();

        $client = new Client([
            'base_uri' => $baseUrl,
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'auth-token' => $apiToken,
            ],
        ]);

        // Try to list available accounts for better error messages (non-blocking)
        $availableAccountIds = [];
        try {
            $listResponse = $client->get('/users/current/accounts', ['http_errors' => false, 'timeout' => 5]);
            if ($listResponse->getStatusCode() === 200) {
                $accounts = json_decode($listResponse->getBody()->getContents(), true);
                if (is_array($accounts)) {
                    foreach ($accounts as $acc) {
                        $metaApiId = $acc['_id'] ?? $acc['id'] ?? $acc['accountId'] ?? null;
                        if ($metaApiId) {
                            $availableAccountIds[] = $metaApiId;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore - we'll handle errors in the main loop
        }

        $updateCount = 0;
        $lastState = null;
        $consecutiveErrors = 0;
        $consecutive404Errors = 0;

        while (true) {
            if (connection_aborted()) {
                break;
            }

            // Send keepalive every 30 seconds
            if ($updateCount % 10 == 0 && $updateCount > 0) {
                echo ": keepalive\n\n";
                flush();
            }

            try {
                // Get account status
                $response = $client->get("/users/current/accounts/{$accountId}", [
                    'http_errors' => false,
                ]);

                $statusCode = $response->getStatusCode();
                
                if ($statusCode === 200) {
                    $consecutiveErrors = 0; // Reset error count on success
                    $accountData = json_decode($response->getBody()->getContents(), true);
                    $currentState = $accountData['state'] ?? 'unknown';
                    
                    // Only send update if state changed or every 5 iterations
                    if ($currentState !== $lastState || $updateCount % 5 == 0) {
                        $accountInfo = [];
                        
                        // Try to get account information if deployed
                        if (in_array($currentState, ['DEPLOYED', 'DEPLOYING'])) {
                            try {
                                $infoResponse = $client->get("/users/current/accounts/{$accountId}/account-information", [
                                    'http_errors' => false,
                                ]);
                                
                                if ($infoResponse->getStatusCode() === 200) {
                                    $accountInfo['accountInformation'] = json_decode($infoResponse->getBody()->getContents(), true);
                                }
                            } catch (\Exception $e) {
                                // Ignore if account not yet synchronized
                            }
                            
                            // Try to get positions
                            try {
                                $posResponse = $client->get("/users/current/accounts/{$accountId}/positions", [
                                    'http_errors' => false,
                                ]);
                                
                                if ($posResponse->getStatusCode() === 200) {
                                    $accountInfo['positions'] = json_decode($posResponse->getBody()->getContents(), true);
                                }
                            } catch (\Exception $e) {
                                // Ignore if not available
                            }
                            
                            // Try to get orders
                            try {
                                $orderResponse = $client->get("/users/current/accounts/{$accountId}/orders", [
                                    'http_errors' => false,
                                ]);
                                
                                if ($orderResponse->getStatusCode() === 200) {
                                    $accountInfo['orders'] = json_decode($orderResponse->getBody()->getContents(), true);
                                }
                            } catch (\Exception $e) {
                                // Ignore if not available
                            }
                        }
                        
                        $data = [
                            'type' => 'status',
                            'account' => [
                                'state' => $currentState,
                                'connected' => in_array($currentState, ['DEPLOYED', 'DEPLOYING', 'CONNECTED']),
                                'connectedToBroker' => $currentState === 'DEPLOYED',
                                'accountInformation' => $accountInfo['accountInformation'] ?? null,
                                'positions' => $accountInfo['positions'] ?? [],
                                'orders' => $accountInfo['orders'] ?? [],
                            ],
                            'timestamp' => now()->toIso8601String(),
                        ];

                        echo "data: " . json_encode($data) . "\n\n";
                        flush();
                        
                        // Update connection status in database based on account state
                        if ($currentState === 'DEPLOYED' && $exchangeConnection->status !== 'active') {
                            // Auto-activate when account is deployed
                            $exchangeConnection->update([
                                'status' => 'active',
                                'is_active' => true,
                                'last_tested_at' => now(),
                                'last_error' => null,
                            ]);
                        } elseif (in_array($currentState, ['DEPLOYING', 'CONNECTING']) && $exchangeConnection->status !== 'testing') {
                            // Mark as testing while deploying
                            $exchangeConnection->update([
                                'status' => 'testing',
                                'last_tested_at' => now(),
                            ]);
                        } elseif (in_array($currentState, ['UNDEPLOYED', 'DISCONNECTED']) && $exchangeConnection->status === 'active') {
                            // Deactivate if account is disconnected
                            $exchangeConnection->update([
                                'status' => 'inactive',
                                'is_active' => false,
                                'last_error' => 'Account disconnected from broker',
                            ]);
                        }
                        
                        $lastState = $currentState;
                    }
                } else {
                    $consecutiveErrors++;
                    $responseBody = $response->getBody()->getContents();
                    $errorData = json_decode($responseBody, true);
                    $errorMessage = $errorData['message'] ?? $errorData['error'] ?? "HTTP {$statusCode}";
                    
                    // If 404, provide more helpful message with available accounts
                    if ($statusCode === 404) {
                        $consecutive404Errors++;
                        $errorMessage = "Account not found. The account ID '{$accountId}' does not exist in MetaApi or has been deleted.";
                        
                        $errorPayload = [
                            'type' => 'error',
                            'message' => $errorMessage,
                            'status_code' => $statusCode,
                            'account_id' => $accountId,
                        ];
                        
                        // Add available accounts info if we have it
                        if (!empty($availableAccountIds)) {
                            $errorPayload['suggestion'] = "You have " . count($availableAccountIds) . " account(s) available. Please verify the account ID matches one of your MetaApi accounts.";
                            $errorPayload['available_account_count'] = count($availableAccountIds);
                            // Only show first 5 account IDs (truncated) to avoid too much data
                            if (count($availableAccountIds) <= 5) {
                                $errorPayload['available_account_ids'] = $availableAccountIds;
                            } else {
                                $errorPayload['available_account_ids'] = array_slice($availableAccountIds, 0, 5);
                                $errorPayload['note'] = 'Showing first 5 of ' . count($availableAccountIds) . ' accounts';
                            }
                        } else {
                            $errorPayload['suggestion'] = "Please verify the account ID in your MetaApi dashboard or recreate the connection.";
                        }
                        
                        // Only send error after first error (immediate feedback for 404)
                        if ($consecutive404Errors === 1 || $updateCount % 5 == 0) {
                            echo "data: " . json_encode($errorPayload) . "\n\n";
                            flush();
                        }
                        
                        // Stop polling after 10 consecutive 404 errors (account definitely doesn't exist)
                        if ($consecutive404Errors >= 10) {
                            echo "data: " . json_encode([
                                'type' => 'error',
                                'message' => 'Stopping monitoring. Account not found after multiple attempts.',
                                'suggestion' => 'Please verify the account ID and try again.',
                            ]) . "\n\n";
                            flush();
                            break; // Exit the loop
                        }
                    } else {
                        // For other errors, wait for 2 consecutive errors
                        if ($consecutiveErrors >= 2) {
                            echo "data: " . json_encode([
                                'type' => 'error',
                                'message' => $errorMessage,
                                'status_code' => $statusCode,
                                'account_id' => $accountId,
                            ]) . "\n\n";
                            flush();
                        }
                    }
                }

            } catch (\Exception $e) {
                $consecutiveErrors++;
                \Log::error('MetaApi monitor error', [
                    'error' => $e->getMessage(),
                    'connection_id' => $exchangeConnection->id,
                    'account_id' => $accountId,
                ]);
                
                // Only send error after 2 consecutive errors
                if ($consecutiveErrors >= 2) {
                    echo "data: " . json_encode([
                        'type' => 'error',
                        'message' => 'Connection error: ' . $e->getMessage(),
                    ]) . "\n\n";
                    flush();
                }
            }

            $updateCount++;
            
            // Update every 3 seconds
            sleep(3);
        }

        return response('', 200);
    }

    /**
     * Generate account token for MetaApi connection
     * 
     * Generates a scoped account token via MetaApi Profile API
     * This token can be used for monitoring connections instead of the main API token
     */
    public function generateAccountToken(ExchangeConnection $exchangeConnection, Request $request)
    {
        if (strtolower($exchangeConnection->provider) !== 'metaapi') {
            return response()->json([
                'success' => false,
                'message' => 'Only MetaApi connections can generate account tokens',
            ], 400);
        }

        $credentials = $exchangeConnection->credentials;
        $accountId = $credentials['account_id'] ?? null;

        if (empty($accountId)) {
            return response()->json([
                'success' => false,
                'message' => 'MetaApi Account ID is required',
            ], 400);
        }

        try {
            $validityHours = $request->input('validity_hours', 'Infinity');
            $accessRules = $request->input('access_rules'); // Optional custom access rules
            $captchaToken = $request->input('captcha_token'); // Optional CAPTCHA token

            $provisioningService = new MetaApiProvisioningService();
            $result = $provisioningService->generateAccountToken(
                $accountId,
                $accessRules,
                $validityHours,
                $captchaToken
            );

            if ($result['success']) {
                // Optionally store the account token in connection credentials
                // (Note: You may want to encrypt this token before storing)
                $credentials['account_token'] = $result['token'];
                $credentials['account_token_generated_at'] = now()->toIso8601String();
                $exchangeConnection->update(['credentials' => $credentials]);

                return response()->json([
                    'success' => true,
                    'message' => 'Account token generated and saved successfully',
                    'token' => $result['token'],
                    'account_id' => $accountId,
                ]);
            }

            return response()->json($result, 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate account token: ' . $e->getMessage(),
            ], 500);
        }
    }
}

