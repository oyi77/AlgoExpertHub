<?php

namespace Addons\TradingManagement\Modules\DataProvider\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use App\Services\GlobalConfigurationService;

/**
 * MetaApi Provisioning Service
 * 
 * Handles adding MT accounts to MetaApi.cloud programmatically
 * 
 * API Documentation: https://metaapi.cloud/docs/provisioning/
 */
class MetaApiProvisioningService
{
    protected Client $client;
    protected string $baseUrl;
    protected ?string $apiToken;
    protected int $timeout;

    public function __construct(?string $apiToken = null)
    {
        // Get token: parameter -> config -> global settings
        $this->apiToken = $apiToken 
            ?? config('trading-management.metaapi.api_token')
            ?? $this->getTokenFromGlobalSettings();
        
        // Get base URL: config -> global settings -> default
        // MetaApi Provisioning API base URL: https://mt-provisioning-api-v1.agiliumtrade.agiliumtrade.ai
        $globalConfig = $this->getGlobalConfig();
        $this->baseUrl = config('trading-management.metaapi.provisioning_base_url')
            ?? ($globalConfig['provisioning_base_url'] ?? null)
            ?? 'https://mt-provisioning-api-v1.agiliumtrade.agiliumtrade.ai';
        
        $this->timeout = config('trading-management.metaapi.timeout')
            ?? ($globalConfig['timeout'] ?? null)
            ?? 30;

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Add MT account to MetaApi
     * 
     * @param array $accountData {
     *   @var string $login MT account number
     *   @var string $password MT account password
     *   @var string $server MT server name
     *   @var string $name Account name (human-readable)
     *   @var string $platform 'mt4' or 'mt5'
     *   @var string|null $provisioningProfileId Optional provisioning profile ID
     *   @var string $type Account type: 'cloud-g1' or 'cloud-g2' (default: 'cloud-g2')
     *   @var int|null $magic Magic number for trades (0 if manualTrades=true)
     *   @var bool $manualTrades Allow manual trades (default: false)
     * }
     * @return array ['success' => bool, 'account_id' => string, 'message' => string, 'data' => array]
     */
    public function addAccount(array $accountData): array
    {
        if (empty($this->apiToken)) {
            return [
                'success' => false,
                'message' => 'MetaApi API token is required. Please configure METAAPI_TOKEN in .env',
            ];
        }

        // Validate required fields
        $required = ['login', 'password', 'server', 'name', 'platform'];
        foreach ($required as $field) {
            if (empty($accountData[$field])) {
                return [
                    'success' => false,
                    'message' => "Field '{$field}' is required",
                ];
            }
        }

        // Generate unique transaction ID (32 characters)
        $transactionId = bin2hex(random_bytes(16));

        // Prepare request body
        $body = [
            'login' => (string) $accountData['login'],
            'password' => $accountData['password'],
            'server' => $accountData['server'],
            'name' => $accountData['name'],
            'platform' => strtolower($accountData['platform']) === 'mt5' ? 'mt5' : 'mt4',
            'type' => $accountData['type'] ?? 'cloud-g2',
        ];

        // Optional fields
        if (!empty($accountData['provisioningProfileId'])) {
            $body['provisioningProfileId'] = $accountData['provisioningProfileId'];
        }

        if (isset($accountData['magic'])) {
            $body['magic'] = (int) $accountData['magic'];
        }

        if (isset($accountData['manualTrades'])) {
            $body['manualTrades'] = (bool) $accountData['manualTrades'];
            if ($body['manualTrades'] && (!isset($body['magic']) || $body['magic'] !== 0)) {
                $body['magic'] = 0;
            }
        }

        try {
            $response = $this->client->post('/users/current/accounts', [
                'headers' => [
                    'auth-token' => $this->apiToken,
                    'transaction-id' => $transactionId,
                ],
                'json' => $body,
                'http_errors' => false, // Don't throw on 4xx/5xx
            ]);
            
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200 && $statusCode !== 201) {
                $responseBody = $response->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['message'] ?? "HTTP {$statusCode}";
                
                if ($statusCode === 401) {
                    $errorMessage = 'Invalid MetaApi API token. Please check your token.';
                } elseif ($statusCode === 400) {
                    $errorMessage = 'Invalid request: ' . ($errorData['message'] ?? 'Bad request');
                } elseif ($statusCode === 409) {
                    $errorMessage = 'Account already exists in MetaApi: ' . ($errorData['message'] ?? 'Conflict');
                }
                
                Log::error('Failed to add MetaApi account', [
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                    'mt_login' => $accountData['login'] ?? 'unknown',
                ]);
                
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'status_code' => $statusCode,
                    'data' => $errorData,
                ];
            }

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['id'])) {
                Log::info('MetaApi account added successfully', [
                    'metaapi_account_id' => $data['id'],
                    'mt_login' => $accountData['login'],
                    'server' => $accountData['server'],
                ]);

                return [
                    'success' => true,
                    'account_id' => $data['id'],
                    'message' => 'Account added to MetaApi successfully',
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'message' => 'Unexpected response format from MetaApi',
                'data' => $data,
            ];
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            $errorData = json_decode($responseBody, true);

            $errorMessage = $errorData['message'] ?? $e->getMessage();

            if ($statusCode === 401) {
                $errorMessage = 'Invalid MetaApi API token. Please check your token.';
            } elseif ($statusCode === 400) {
                $errorMessage = 'Invalid request: ' . ($errorData['message'] ?? 'Bad request');
            } elseif ($statusCode === 409) {
                $errorMessage = 'Account already exists in MetaApi: ' . ($errorData['message'] ?? 'Conflict');
            }

            Log::error('Failed to add MetaApi account', [
                'status_code' => $statusCode,
                'error' => $errorMessage,
                'mt_login' => $accountData['login'] ?? 'unknown',
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
                'status_code' => $statusCode,
                'data' => $errorData,
            ];
        }
    }

    /**
     * Get account status from MetaApi
     * 
     * @param string $metaApiAccountId MetaApi account ID
     * @return array ['success' => bool, 'status' => string, 'data' => array]
     */
    public function getAccountStatus(string $metaApiAccountId): array
    {
        if (empty($this->apiToken)) {
            return [
                'success' => false,
                'message' => 'MetaApi API token is required',
            ];
        }

        try {
            $response = $this->client->get("/users/current/accounts/{$metaApiAccountId}", [
                'headers' => [
                    'auth-token' => $this->apiToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'status' => $data['state'] ?? $data['connectionStatus'] ?? 'unknown',
                'data' => $data,
            ];
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $errorMessage = $e->hasResponse() 
                ? json_decode($e->getResponse()->getBody()->getContents(), true)['message'] ?? $e->getMessage()
                : $e->getMessage();

            return [
                'success' => false,
                'message' => $errorMessage,
                'status_code' => $statusCode,
            ];
        }
    }

    /**
     * Delete account from MetaApi
     * 
     * @param string $metaApiAccountId MetaApi account ID
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteAccount(string $metaApiAccountId): array
    {
        if (empty($this->apiToken)) {
            return [
                'success' => false,
                'message' => 'MetaApi API token is required',
            ];
        }

        $transactionId = bin2hex(random_bytes(16));

        try {
            $response = $this->client->delete("/users/current/accounts/{$metaApiAccountId}", [
                'headers' => [
                    'auth-token' => $this->apiToken,
                    'transaction-id' => $transactionId,
                ],
            ]);

            return [
                'success' => true,
                'message' => 'Account deleted from MetaApi successfully',
            ];
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $errorMessage = $e->hasResponse() 
                ? json_decode($e->getResponse()->getBody()->getContents(), true)['message'] ?? $e->getMessage()
                : $e->getMessage();

            return [
                'success' => false,
                'message' => $errorMessage,
                'status_code' => $statusCode,
            ];
        }
    }

    /**
     * List all accounts in MetaApi
     * 
     * @return array ['success' => bool, 'accounts' => array]
     */
    public function listAccounts(): array
    {
        if (empty($this->apiToken)) {
            return [
                'success' => false,
                'message' => 'MetaApi API token is required',
            ];
        }

        try {
            // Note: This endpoint might be different, check MetaApi docs
            // For now, we'll use a placeholder endpoint
            $response = $this->client->get('/users/current/accounts', [
                'headers' => [
                    'auth-token' => $this->apiToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'accounts' => is_array($data) ? $data : [],
            ];
        } catch (RequestException $e) {
            // Endpoint might not exist, return empty list
            return [
                'success' => true,
                'accounts' => [],
                'note' => 'List endpoint may not be available',
            ];
        }
    }

    /**
     * Get billing information (balance, trial amount, subscription)
     * 
     * Uses Billing API endpoints:
     * - GET /users/current/balance - Returns MetaApiBillingBalance (amount, trialAmount)
     * - GET /users/current/billing-statuses - Returns billing statuses (planId, accessTerminated, amountPastDue)
     * 
     * @return array ['success' => bool, 'balance' => float, 'trial_amount' => float, 'spending_credits' => float, 'subscription' => array, 'billing_statuses' => array, 'data' => array]
     */
    public function getBillingInfo(): array
    {
        if (empty($this->apiToken)) {
            return [
                'success' => false,
                'message' => 'MetaApi API token is required',
            ];
        }

        // Try Billing API first
        $globalConfig = $this->getGlobalConfig();
        $billingBaseUrl = config('trading-management.metaapi.billing_base_url')
            ?? $globalConfig['billing_base_url']
            ?? 'https://billing-api-v1.agiliumtrade.agiliumtrade.ai';
        
        try {
            $billingClient = new Client([
                'base_uri' => $billingBaseUrl,
                'timeout' => $this->timeout,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'auth-token' => $this->apiToken,
                ],
            ]);

            // Get balance from /users/current/balance endpoint
            // Returns MetaApiBillingBalance with 'amount' and 'trialAmount'
            $response = $billingClient->get('/users/current/balance', [
                'timeout' => 10,
                'connect_timeout' => 5,
                'http_errors' => false,
            ]);
            
            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $balanceData = json_decode($response->getBody()->getContents(), true);
                
                // Get billing statuses for subscription info
                $billingStatuses = [];
                try {
                    $statusResponse = $billingClient->get('/users/current/billing-statuses', [
                        'timeout' => 10,
                        'connect_timeout' => 5,
                        'http_errors' => false,
                    ]);
                    
                    if ($statusResponse->getStatusCode() === 200) {
                        $billingStatuses = json_decode($statusResponse->getBody()->getContents(), true);
                    }
                } catch (\Exception $e) {
                    // Billing statuses not available, continue with balance only
                    Log::debug('Failed to fetch billing statuses', ['error' => $e->getMessage()]);
                }
                
                // Extract balance information
                $balance = (float) ($balanceData['amount'] ?? 0);
                $trialAmount = (float) ($balanceData['trialAmount'] ?? 0);
                $totalAvailable = $balance + $trialAmount;
                
                // Extract subscription info from billing statuses if available
                $subscription = [];
                $usage = [];
                if (!empty($billingStatuses) && is_array($billingStatuses)) {
                    $firstStatus = $billingStatuses[0] ?? [];
                    $subscription = [
                        'plan_id' => $firstStatus['planId'] ?? null,
                        'team_id' => $firstStatus['_id'] ?? null,
                        'access_terminated' => $firstStatus['accessTerminated'] ?? false,
                        'amount_past_due' => $firstStatus['amountPastDue'] ?? 0,
                    ];
                }
                
                return [
                    'success' => true,
                    'balance' => $balance,
                    'trial_amount' => $trialAmount,
                    'spending_credits' => $trialAmount, // Trial amount can be considered as spending credits
                    'subscription' => $subscription,
                    'usage' => $usage,
                    'total_available' => $totalAvailable,
                    'billing_statuses' => $billingStatuses,
                    'data' => $balanceData,
                ];
            } else {
                $responseBody = $response->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['message'] ?? "HTTP {$statusCode}";
                
                if ($statusCode === 401) {
                    $errorMessage = 'Invalid MetaApi API token. Please check your token.';
                } elseif ($statusCode === 403) {
                    $errorMessage = 'Access forbidden. Your token may not have permission to access billing information.';
                }
                
                Log::info('Billing API balance endpoint returned error', [
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                ]);
            }
        } catch (\Exception $e) {
            // Billing API failed
            Log::debug('Billing API not available', ['error' => $e->getMessage()]);
        }

        // If Billing API failed, return informative error
        return [
            'success' => false,
            'message' => 'Billing API endpoint not available. Please check your API token permissions and subscription plan.',
            'note' => 'Endpoint used: /users/current/balance. Requires billing-api:rest:public:payment:getUserBalance method with reader role.',
        ];
    }

    /**
     * Deposit amount to MetaApi account balance
     * 
     * Uses POST /users/current/deposit endpoint from Billing API
     * Requires billing-api:rest:public:payment:depositToUserAccount method with writer role
     * Requires access to billing-status resource
     * 
     * @param float $amount Deposit amount
     * @param bool $termsAgreement User agreement to terms and conditions
     * @param bool $refundAgreement User agreement to refund policy
     * @return array ['success' => bool, 'message' => string, 'client_secret' => string|null, 'data' => array]
     */
    public function deposit(float $amount, bool $termsAgreement = true, bool $refundAgreement = true): array
    {
        if (empty($this->apiToken)) {
            return [
                'success' => false,
                'message' => 'MetaApi API token is required',
            ];
        }

        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Deposit amount must be greater than 0',
            ];
        }

        if (!$termsAgreement || !$refundAgreement) {
            return [
                'success' => false,
                'message' => 'You must agree to terms and conditions and refund policy',
            ];
        }

        // Use Billing API
        $globalConfig = $this->getGlobalConfig();
        $billingBaseUrl = config('trading-management.metaapi.billing_base_url')
            ?? $globalConfig['billing_base_url']
            ?? 'https://billing-api-v1.agiliumtrade.agiliumtrade.ai';

        try {
            $billingClient = new Client([
                'base_uri' => $billingBaseUrl,
                'timeout' => $this->timeout,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'auth-token' => $this->apiToken,
                ],
            ]);

            // POST /users/current/deposit
            $response = $billingClient->post('/users/current/deposit', [
                'json' => [
                    'amount' => $amount,
                    'termsAgreement' => $termsAgreement,
                    'refundAgreement' => $refundAgreement,
                ],
                'timeout' => 30,
                'connect_timeout' => 10,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode === 201) {
                $data = json_decode($response->getBody()->getContents(), true);
                
                return [
                    'success' => true,
                    'message' => 'Deposit request processed successfully',
                    'client_secret' => $data['client_secret'] ?? null, // For Stripe 3DS
                    'data' => $data,
                ];
            } else {
                $responseBody = $response->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['message'] ?? "HTTP {$statusCode}";
                
                if ($statusCode === 400) {
                    $errorMessage = 'User does not have billing status or payment method. Please set up payment method first.';
                } elseif ($statusCode === 401) {
                    $errorMessage = 'Invalid MetaApi API token. Please check your token.';
                } elseif ($statusCode === 403) {
                    $errorMessage = 'Access forbidden. Your token may not have permission to deposit.';
                }
                
                Log::info('Billing API deposit endpoint returned error', [
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                    'amount' => $amount,
                ]);
                
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'status_code' => $statusCode,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Billing API deposit failed', [
                'error' => $e->getMessage(),
                'amount' => $amount,
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to process deposit: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get account statistics from MetaApi API
     * 
     * Uses:
     * - GET /users/current/accounts/count - Returns total account count (more efficient)
     * - GET /users/current/accounts/deployed-account-count - Returns deployed account count
     * - GET /users/current/accounts - Returns full account list (fallback for detailed stats)
     * 
     * @return array ['success' => bool, 'total_accounts' => int, 'active_accounts' => int, 'inactive_accounts' => int, 'deployed_accounts' => int, 'source' => string, 'data' => array]
     */
    public function getAccountStats(): array
    {
        if (empty($this->apiToken)) {
            return [
                'success' => false,
                'message' => 'MetaApi API token is required',
            ];
        }

        // Try to get account count from MetaApi API first (more efficient)
        $totalCount = null;
        $deployedCount = null;
        $apiAccounts = [];
        
        try {
            // Get total account count
            $countResponse = $this->client->get('/users/current/accounts/count', [
                'headers' => [
                    'auth-token' => $this->apiToken,
                ],
                'timeout' => 10,
                'connect_timeout' => 5,
                'http_errors' => false,
            ]);
            
            if ($countResponse->getStatusCode() === 200) {
                $countData = json_decode($countResponse->getBody()->getContents(), true);
                $totalCount = (int) ($countData['count'] ?? 0);
            }
            
            // Get deployed account count
            $deployedResponse = $this->client->get('/users/current/accounts/deployed-account-count', [
                'headers' => [
                    'auth-token' => $this->apiToken,
                ],
                'timeout' => 10,
                'connect_timeout' => 5,
                'http_errors' => false,
            ]);
            
            if ($deployedResponse->getStatusCode() === 200) {
                $deployedData = json_decode($deployedResponse->getBody()->getContents(), true);
                $deployedCount = (int) ($deployedData['count'] ?? 0);
            }
            
            // If we have counts, try to get full list for detailed stats (active/inactive)
            if ($totalCount !== null) {
                $response = $this->client->get('/users/current/accounts', [
                    'headers' => [
                        'auth-token' => $this->apiToken,
                    ],
                    'timeout' => 10,
                    'connect_timeout' => 5,
                    'http_errors' => false,
                ]);
                
                $statusCode = $response->getStatusCode();
                if ($statusCode === 200) {
                    $apiAccounts = json_decode($response->getBody()->getContents(), true);
                    
                    // Handle both array and object responses
                    if (!is_array($apiAccounts)) {
                        $apiAccounts = [];
                    }
                }
            } else {
                // Fallback: try full accounts list if count endpoint fails
                $response = $this->client->get('/users/current/accounts', [
                    'headers' => [
                        'auth-token' => $this->apiToken,
                    ],
                    'timeout' => 10,
                    'connect_timeout' => 5,
                    'http_errors' => false,
                ]);
                
                $statusCode = $response->getStatusCode();
                if ($statusCode === 200) {
                    $apiAccounts = json_decode($response->getBody()->getContents(), true);
                    
                    // Handle both array and object responses
                    if (!is_array($apiAccounts)) {
                        $apiAccounts = [];
                    }
                    
                    $totalCount = count($apiAccounts);
                } else {
                    $responseBody = $response->getBody()->getContents();
                    $errorData = json_decode($responseBody, true);
                    $errorMessage = $errorData['message'] ?? "HTTP {$statusCode}";
                    
                    if ($statusCode === 401) {
                        $errorMessage = 'Invalid MetaApi API token. Please check your token.';
                    } elseif ($statusCode === 404) {
                        $errorMessage = 'Accounts endpoint not found. Please check the API endpoint path.';
                    }
                    
                    // Log and fall through to use local database
                    Log::debug('MetaApi accounts endpoint returned error, using local database', [
                        'status_code' => $statusCode,
                        'error' => $errorMessage,
                    ]);
                }
            }
            
            // Process accounts if we have them
            if (count($apiAccounts) > 0) {
                // Use API data if available
                $activeFromApi = 0;
                $inactiveFromApi = 0;
                
                foreach ($apiAccounts as $account) {
                    $state = strtoupper($account['state'] ?? $account['connectionStatus'] ?? $account['status'] ?? '');
                    if (in_array($state, ['DEPLOYED', 'CONNECTED', 'DEPLOYING', 'ACTIVE'])) {
                        $activeFromApi++;
                    } else {
                        $inactiveFromApi++;
                    }
                }
                
                return [
                    'success' => true,
                    'total_accounts' => $totalCount ?? count($apiAccounts),
                    'active_accounts' => $activeFromApi,
                    'inactive_accounts' => $inactiveFromApi,
                    'deployed_accounts' => $deployedCount ?? $activeFromApi,
                    'source' => 'metaapi_api',
                    'data' => [
                        'accounts' => $apiAccounts,
                        'count' => $totalCount,
                        'deployed_count' => $deployedCount,
                    ],
                ];
            } elseif ($totalCount !== null) {
                // We have count but no detailed list, return counts only
                return [
                    'success' => true,
                    'total_accounts' => $totalCount,
                    'active_accounts' => $deployedCount ?? null,
                    'inactive_accounts' => null,
                    'deployed_accounts' => $deployedCount ?? null,
                    'source' => 'metaapi_api',
                    'note' => 'Using count endpoints. Detailed stats require /users/current/accounts endpoint.',
                    'data' => [
                        'count' => $totalCount,
                        'deployed_count' => $deployedCount,
                    ],
                ];
            }
        } catch (RequestException $e) {
            // API endpoint may not be available, use local data
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $isConnectionError = strpos($e->getMessage(), 'Could not resolve host') !== false 
                || strpos($e->getMessage(), 'Connection timed out') !== false
                || strpos($e->getMessage(), 'cURL error') !== false;
            
            // Only log as debug for connection errors, info for API errors
            if ($isConnectionError || $statusCode === 0) {
                Log::debug('MetaApi accounts list endpoint not available, using local database', [
                    'error' => $e->getMessage(),
                ]);
            } else {
                Log::info('MetaApi accounts list endpoint returned error, using local database', [
                    'status_code' => $statusCode,
                    'error' => $e->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            // Handle other exceptions (like connection errors)
            $isConnectionError = strpos($e->getMessage(), 'Could not resolve host') !== false 
                || strpos($e->getMessage(), 'Connection timed out') !== false
                || strpos($e->getMessage(), 'cURL error') !== false
                || strpos($e->getMessage(), 'Invalid provisioning API base URL') !== false
                || strpos($e->getMessage(), 'Cannot resolve host') !== false;
            
            if ($isConnectionError) {
                Log::debug('MetaApi accounts list endpoint connection failed, using local database', [
                    'error' => $e->getMessage(),
                ]);
            } else {
                Log::info('MetaApi accounts list endpoint error, using local database', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback to local database if API is not available
        try {
            // Try to query by provider column first
            $metaApiConnections = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('provider', 'metaapi')
                ->get();
        } catch (\Exception $e) {
            // If provider column doesn't exist, try to filter by credentials
            try {
                $allConnections = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::get();
                $metaApiConnections = $allConnections->filter(function($conn) {
                    $creds = $conn->credentials ?? [];
                    // Check if credentials contain MetaApi-specific fields
                    return isset($creds['account_id']) || isset($creds['api_token']) || 
                           (isset($creds['provider']) && $creds['provider'] === 'metaapi');
                });
            } catch (\Exception $e2) {
                Log::warning('Failed to get MetaApi connections from database', [
                    'error' => $e->getMessage(), 
                    'fallback_error' => $e2->getMessage()
                ]);
                $metaApiConnections = collect([]);
            }
        }

        $totalAccounts = $metaApiConnections->count();
        $activeAccounts = $metaApiConnections->where('status', 'connected')->where('is_active', 1)->count();
        $inactiveAccounts = $totalAccounts - $activeAccounts;

        return [
            'success' => true,
            'total_accounts' => $totalAccounts,
            'active_accounts' => $activeAccounts,
            'inactive_accounts' => $inactiveAccounts,
            'source' => 'local_database',
            'note' => 'Stats from local database. API endpoint may not be available.',
        ];
    }

    /**
     * Get global config
     * 
     * @return array
     */
    protected function getGlobalConfig(): array
    {
        try {
            return GlobalConfigurationService::get('metaapi_global_settings', []);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get current user information from MetaApi
     * 
     * Note: /users/current endpoint doesn't exist in Provisioning API
     * Use getBillingInfo() for billing information instead
     * 
     * @return array ['success' => bool, 'user' => array, 'data' => array]
     */
    public function getUserInfo(): array
    {
        // /users/current doesn't exist, return error
        return [
            'success' => false,
            'message' => 'User info endpoint not available. Use getBillingInfo() for billing information.',
        ];
    }

    /**
     * Get API token from global settings
     * 
     * @return string|null
     */
    protected function getTokenFromGlobalSettings(): ?string
    {
        try {
            $globalConfig = $this->getGlobalConfig();
            
            if (!empty($globalConfig['api_token'])) {
                try {
                    return Crypt::decryptString($globalConfig['api_token']);
                } catch (\Exception $e) {
                    return $globalConfig['api_token'];
                }
            }
        } catch (\Exception $e) {
            Log::debug('Failed to get MetaApi token from global settings', ['error' => $e->getMessage()]);
        }
        
        return null;
    }
}
