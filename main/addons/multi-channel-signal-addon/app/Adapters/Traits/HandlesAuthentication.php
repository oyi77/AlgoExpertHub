<?php

namespace Addons\MultiChannelSignalAddon\App\Adapters\Traits;

use Illuminate\Support\Facades\Log;
use function Amp\Future\await;
use Revolt\EventLoop;

trait HandlesAuthentication
{
    /**
     * Start authentication process.
     * Returns QR code or phone number request.
     *
     * @return array
     */
    public function startAuth(): array
    {
        // CRITICAL: This method should ONLY be called during POST requests
        // If called during GET, it will output web UI via start()
        // Check if we're in a web context and this is a GET request
        $isGetRequest = false;
        if (PHP_SAPI !== 'cli') {
            // Check multiple ways to detect GET request
            if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $isGetRequest = true;
            } elseif (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                $isGetRequest = true;
            } elseif (!isset($_POST) || empty($_POST)) {
                // If no POST data and we're in web context, assume GET
                $isGetRequest = true;
            }
        }
        
        if ($isGetRequest) {
            // Don't initialize MadelineProto during GET requests
            // This prevents start() from being called and outputting web UI
            return [
                'type' => 'phone_required',
                'message' => 'Please submit the form to start authentication'
            ];
        }
        
        try {
            if (!class_exists('\danog\MadelineProto\API')) {
                return [
                    'type' => 'error',
                    'message' => 'MadelineProto not installed'
                ];
            }

            $apiId = $this->getConfig('api_id');
            $apiHash = $this->getConfig('api_hash');

            if (empty($apiId) || empty($apiHash)) {
                return [
                    'type' => 'error',
                    'message' => 'API ID and API Hash required'
                ];
            }

            // CRITICAL: Set programmatic auth mode BEFORE initializing MadelineProto
            // This prevents start() from outputting web UI
            putenv('MADELINE_PROGRAMMATIC_AUTH=1');
            $_ENV['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            
            // Also set in $_SERVER for compatibility
            $_SERVER['MADELINE_PROGRAMMATIC_AUTH'] = '1';

            // Session file path (per channel source)
            // Admin channels use separate namespace
            if ($this->channelSource->isAdminOwned()) {
                $this->sessionFile = storage_path('app/madelineproto/admin/' . $this->channelSource->id . '.madeline');
            } else {
                $this->sessionFile = storage_path('app/madelineproto/' . $this->channelSource->id . '.madeline');
            }
            
            $sessionDir = dirname($this->sessionFile);
            if (!is_dir($sessionDir)) {
                mkdir($sessionDir, 0755, true);
            }

            // Settings for MadelineProto v8
            $appInfo = new \danog\MadelineProto\Settings\AppInfo();
            $appInfo->setApiId((int) $apiId);
            $appInfo->setApiHash($apiHash);
            
            $settings = new \danog\MadelineProto\Settings();
            $settings->setAppInfo($appInfo);
            
            // Disable internal logging, use Laravel logs
            // Use CALLABLE_LOGGER with no-op function to disable logging
            $logger = new \danog\MadelineProto\Settings\Logger();
            $logger->setType(\danog\MadelineProto\Logger::CALLABLE_LOGGER);
            $logger->setExtra(function () {}); // No-op callable
            $settings->setLogger($logger);
            
            $this->madeline = new \danog\MadelineProto\API($this->sessionFile, $settings);
            
            // Store original POST data (including CSRF token for Laravel)
            $originalPost = $_POST ?? [];
            $csrfToken = $originalPost['_token'] ?? null;
            
            // CRITICAL: Get phone number BEFORE calling start()
            // We need it to call phoneLogin() directly without triggering web UI
            $phone = $this->getConfig('phone_number');

            if (empty($phone)) {
                // Request phone number
                return [
                    'type' => 'phone_required',
                    'message' => 'Phone number required'
                ];
            }
            
            // CRITICAL: The issue is that start() outputs HTML when called in web context
            // phoneLogin() internally uses methodCallAsyncRead() which should work without start()
            // BUT: methodCallAsyncRead() might trigger initialization which calls start()
            // SOLUTION: Use the MTProto API's methodCallAsyncRead() directly, bypassing the wrapper
            
            // Clear $_POST to prevent start() from detecting web context
            // But preserve CSRF token for Laravel validation
            $_POST = [];
            if ($csrfToken) {
                $_POST['_token'] = $csrfToken;
            }
            
            // Use output buffering to catch any output (though getOutputBufferStream() bypasses this)
            ob_start();
            ob_start();
            ob_start();
            
            try {
                // CRITICAL: Check authorization state before calling phoneLogin()
                // If already logged in, we need to logout first or skip phoneLogin()
                $sentCode = null;
                if (!EventLoop::getDriver()) {
                    EventLoop::run(function () use ($phone, &$sentCode) {
                        // Call phoneLogin() - it will check if already logged in internally
                        // If it throws "already logged in" exception, logout and retry
                        try {
                            $result = $this->madeline->phoneLogin($phone);
                        } catch (\danog\MadelineProto\Exception $e) {
                            // If already logged in, logout first and retry
                            if (strpos($e->getMessage(), 'already logged') !== false || 
                                strpos($e->getMessage(), 'already_loggedIn') !== false) {
                                Log::info("API already logged in, logging out to start fresh authentication");
                                try {
                                    $this->madeline->logout();
                                    // Retry phoneLogin after logout
                                    $result = $this->madeline->phoneLogin($phone);
                                } catch (\Exception $logoutError) {
                                    Log::error("Failed to logout and retry: " . $logoutError->getMessage());
                                    throw $e; // Re-throw original exception
                                }
                            } else {
                                throw $e; // Re-throw if it's a different error
                            }
                        }
                        
                        // Log what we got for debugging
                        Log::debug("phoneLogin() result type", [
                            'type' => gettype($result),
                            'is_array' => is_array($result),
                            'is_future' => $result instanceof \Amp\Future,
                            'has_underscore' => is_array($result) && isset($result['_'])
                        ]);
                        
                        // If it's already an array (synchronous), use it
                        if (is_array($result) && isset($result['_'])) {
                            $sentCode = $result;
                        } elseif ($result instanceof \Amp\Future) {
                            // It's a Future, await it
                            $sentCode = await([$result])[0];
                        } else {
                            // Unexpected type, use as-is
                            $sentCode = $result;
                        }
                    });
                } else {
                    // We're already in an event loop context
                    // Call phoneLogin() - it will check if already logged in internally
                    // If it throws "already logged in" exception, logout and retry
                    try {
                        $result = $this->madeline->phoneLogin($phone);
                    } catch (\danog\MadelineProto\Exception $e) {
                        // If already logged in, logout first and retry
                        if (strpos($e->getMessage(), 'already logged') !== false || 
                            strpos($e->getMessage(), 'already_loggedIn') !== false) {
                            Log::info("API already logged in, logging out to start fresh authentication");
                            try {
                                $this->madeline->logout();
                                // Retry phoneLogin after logout
                                $result = $this->madeline->phoneLogin($phone);
                            } catch (\Exception $logoutError) {
                                Log::error("Failed to logout and retry: " . $logoutError->getMessage());
                                throw $e; // Re-throw original exception
                            }
                        } else {
                            throw $e; // Re-throw if it's a different error
                        }
                    }
                    
                    // Log what we got for debugging
                    Log::debug("phoneLogin() result type", [
                        'type' => gettype($result),
                        'is_array' => is_array($result),
                        'is_future' => $result instanceof \Amp\Future,
                        'has_underscore' => is_array($result) && isset($result['_'])
                    ]);
                    
                    // If it's already an array (synchronous), use it
                    if (is_array($result) && isset($result['_'])) {
                        $sentCode = $result;
                    } elseif ($result instanceof \Amp\Future) {
                        // It's a Future, await it
                        $sentCode = await([$result])[0];
                    } else {
                        // Unexpected type, use as-is
                        $sentCode = $result;
                    }
                }
                
                // Restore original POST
                $_POST = $originalPost;
                
                // Clear all output buffers
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                
                // Disable automatic update handling (v8 uses setNoop() method)
                try {
                    $this->madeline->setNoop();
                } catch (\Exception $e) {
                    // setNoop() might fail if not initialized, that's okay
                    Log::debug("setNoop() failed: " . $e->getMessage());
                }
                
                // Handle response from phoneLogin
                // phoneLogin() returns the authorization object directly (auth.sentCode)
                // It sets $this->authorized = WAITING_CODE internally
                if (isset($sentCode['_'])) {
                    // Check if it's a sentCode object (this is what phoneLogin() returns)
                    if ($sentCode['_'] === 'auth.sentCode' || isset($sentCode['phone_code_hash'])) {
                        return [
                            'type' => 'code_required',
                            'message' => 'Enter verification code',
                            'phone_code_hash' => $sentCode['phone_code_hash'] ?? null,
                            'sent_code' => $sentCode
                        ];
                    }
                    // Check if already authorized (shouldn't happen, but handle it)
                    if ($sentCode['_'] === 'auth.authorization') {
                        // Already logged in somehow
                        $self = null;
                        try {
                            if (!EventLoop::getDriver()) {
                                EventLoop::run(function () use (&$self) {
                                    $self = await($this->madeline->getSelf());
                                });
                            } else {
                                $self = await($this->madeline->getSelf());
                            }
                            $this->connected = true;
                            return [
                                'type' => 'success',
                                'message' => 'Already authenticated',
                                'user' => $self
                            ];
                        } catch (\Exception $e) {
                            // getSelf() failed, return error
                            return [
                                'type' => 'error',
                                'message' => 'Authentication state unclear: ' . $e->getMessage()
                            ];
                        }
                    }
                }
                
                // Fallback: check for phone_code_hash in response
                if (isset($sentCode['phone_code_hash'])) {
                    return [
                        'type' => 'code_required',
                        'message' => 'Enter verification code',
                        'phone_code_hash' => $sentCode['phone_code_hash']
                    ];
                }
                
                // If we get here, the response is unexpected
                Log::warning("Unexpected phoneLogin() response", ['response' => $sentCode]);
                return [
                    'type' => 'error',
                    'message' => 'Unexpected response from Telegram: ' . json_encode($sentCode)
                ];
                
            } catch (\Exception $e) {
                // Restore original POST
                $_POST = $originalPost;
                
                // Clear all output buffers even on error
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                
                $errorMessage = $e->getMessage();
                
                // If phoneLogin() fails because start() wasn't called, we have a problem
                // phoneLogin() might require start() to be called first
                // But calling start() will output HTML, which we're trying to avoid
                // This is a catch-22 situation
                
                Log::error("MadelineProto phoneLogin error (tried without start()): " . $errorMessage, [
                    'exception' => $e,
                    'api_id' => $apiId,
                    'phone' => substr($phone, 0, 3) . '***'
                ]);
                
                // Check if error indicates we need to call start() first
                if (strpos($errorMessage, 'not started') !== false || 
                    strpos($errorMessage, 'not initialized') !== false ||
                    strpos($errorMessage, 'must be called') !== false) {
                    // phoneLogin() requires start() to be called first
                    // But start() will output HTML, so we can't use it
                    // Return an error explaining the situation
                    return [
                        'type' => 'error',
                        'message' => 'MadelineProto requires initialization, but this would trigger a web UI. Please ensure your session file is valid or use CLI authentication.'
                    ];
                }
                
                // Check for UPDATE_APP_TO_LOGIN error
                if (strpos($errorMessage, 'UPDATE_APP_TO_LOGIN') !== false) {
                    $helpMessage = 'UPDATE_APP_TO_LOGIN Error: Your API credentials appear to be tied to an outdated API layer. ';
                    $helpMessage .= 'Please create a new application at https://my.telegram.org/apps and use the new API ID and API Hash.';
                    return [
                        'type' => 'error',
                        'message' => $helpMessage
                    ];
                }
                
                if (strpos($errorMessage, 'PHONE_NUMBER_INVALID') !== false) {
                    return [
                        'type' => 'error',
                        'message' => 'Invalid phone number format. Please use international format (e.g., +1234567890).'
                    ];
                }
                
                return [
                    'type' => 'error',
                    'message' => 'Failed to send verification code: ' . $errorMessage . '. Please check your API credentials and phone number format.'
                ];
            }
        } catch (\Exception $e) {
            // Restore original POST
            $_POST = $originalPost ?? [];
            
            // Clear all output buffers
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            
            Log::error("startAuth() outer exception: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'type' => 'error',
                'message' => 'Authentication failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Complete authentication with verification code.
     *
     * @param string $code
     * @param string $phoneCodeHash
     * @return array
     */
    public function completeAuth(string $code, string $phoneCodeHash): array
    {
        try {
            // CRITICAL: Refresh channel source and config to ensure we have latest data
            $this->channelSource->refresh();
            $this->config = $this->channelSource->config ?? [];
            
            // Log config for debugging
            Log::info("TelegramMtprotoAdapter::completeAuth - Config check", [
                'channel_id' => $this->channelSource->id,
                'config_keys' => array_keys($this->config),
                'has_phone_number' => isset($this->config['phone_number']),
                'phone_number' => $this->config['phone_number'] ?? 'NOT SET',
                'raw_config' => $this->config
            ]);
            
            // CRITICAL: Ensure MadelineProto API is initialized
            // If not initialized, we need to create it (but don't call start() which outputs HTML)
            if (!$this->madeline) {
                // Initialize the API instance without calling start()
                $apiId = $this->getConfig('api_id');
                $apiHash = $this->getConfig('api_hash');

                if (empty($apiId) || empty($apiHash)) {
                    return [
                        'type' => 'error',
                        'message' => 'API ID and API Hash required'
                    ];
                }

                // Session file path
                if ($this->channelSource->isAdminOwned()) {
                    $this->sessionFile = storage_path('app/madelineproto/admin/' . $this->channelSource->id . '.madeline');
                } else {
                    $this->sessionFile = storage_path('app/madelineproto/' . $this->channelSource->id . '.madeline');
                }
                
                $sessionDir = dirname($this->sessionFile);
                if (!is_dir($sessionDir)) {
                    mkdir($sessionDir, 0755, true);
                }

                // Settings for MadelineProto v8
                $appInfo = new \danog\MadelineProto\Settings\AppInfo();
                $appInfo->setApiId((int) $apiId);
                $appInfo->setApiHash($apiHash);
                
                $settings = new \danog\MadelineProto\Settings();
                $settings->setAppInfo($appInfo);
                
                // Disable internal logging
                $logger = new \danog\MadelineProto\Settings\Logger();
                $logger->setType(\danog\MadelineProto\Logger::CALLABLE_LOGGER);
                $logger->setExtra(function () {});
                $settings->setLogger($logger);
                
                $this->madeline = new \danog\MadelineProto\API($this->sessionFile, $settings);
            }

            // CRITICAL: completePhoneLogin() requires the authorization state from phoneLogin()
            // It checks $this->authorized === WAITING_CODE and uses $this->authorization['phone_code_hash']
            // Since we're using a new adapter instance, we need to use the lower-level API method directly
            // Use auth.signIn directly with the phone_code_hash we stored in the session
            
            // Get phone number from config
            $phone = $this->getConfig('phone_number');
            if (empty($phone)) {
                // Log detailed error for debugging
                Log::error("Phone number not found in config", [
                    'channel_id' => $this->channelSource->id,
                    'config_keys' => array_keys($this->config),
                    'full_config' => $this->config,
                    'channel_source_config' => $this->channelSource->config
                ]);
                
                return [
                    'type' => 'error',
                    'message' => 'Phone number not found in config. Please go back and re-enter your phone number.'
                ];
            }
            
            // CRITICAL: completePhoneLogin() requires authorization state from phoneLogin()
            // We need to set up the authorization state manually before calling completePhoneLogin()
            // Both authorized and authorization are public properties in MTProto trait
            // completePhoneLogin() calls async methods internally, so we need to run it in async context
            $authorization = null;
            try {
                // Always run in async context since completePhoneLogin() calls async methods
                if (!EventLoop::getDriver()) {
                    EventLoop::run(function () use ($phone, $code, $phoneCodeHash, &$authorization) {
                        // Set up authorization state - both are public properties in MTProto trait
                        $this->madeline->authorized = \danog\MadelineProto\API::WAITING_CODE;
                        $this->madeline->authorization = [
                            'phone_number' => $phone,
                            'phone_code_hash' => $phoneCodeHash,
                            '_' => 'auth.sentCode' // Required structure
                        ];
                        
                        // completePhoneLogin() calls async methods internally but returns array synchronously
                        $authorization = $this->madeline->completePhoneLogin($code);
                    });
                } else {
                    // EventLoop driver exists, but we still need async context for completePhoneLogin()
                    // Use async() helper to run in async context - async() returns a Future
                    $future = async(function () use ($phone, $code, $phoneCodeHash) {
                        // Set up authorization state
                        $this->madeline->authorized = \danog\MadelineProto\API::WAITING_CODE;
                        $this->madeline->authorization = [
                            'phone_number' => $phone,
                            'phone_code_hash' => $phoneCodeHash,
                            '_' => 'auth.sentCode'
                        ];
                        
                        // completePhoneLogin() calls async methods internally but returns array synchronously
                        return $this->madeline->completePhoneLogin($code);
                    });
                    
                    // Await the Future - await() expects array, so wrap it
                    $authorization = await([$future])[0];
                }
            } catch (\danog\MadelineProto\RPCError\SessionPasswordNeededError $e) {
                // Handle 2FA password requirement
                // The authorization object should contain password info
                $hint = '';
                try {
                    // Try to get hint from authorization if available
                    if (isset($this->madeline->authorization['hint'])) {
                        $hint = $this->madeline->authorization['hint'];
                    }
                } catch (\Exception $e2) {
                    // Ignore - hint not critical
                }
                
                return [
                    'type' => 'password_required',
                    'message' => 'Two-factor authentication is enabled. Password required.',
                    'hint' => $hint,
                    'has_recovery' => false,
                ];
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                // Handle 2FA (SESSION_PASSWORD_NEEDED) - fallback check
                if ($e->rpc === 'SESSION_PASSWORD_NEEDED') {
                    $hint = '';
                    try {
                        // Try to get hint from authorization if available
                        if (isset($this->madeline->authorization['hint'])) {
                            $hint = $this->madeline->authorization['hint'];
                        }
                    } catch (\Exception $e2) {
                        // Ignore - hint not critical
                    }
                    
                    return [
                        'type' => 'password_required',
                        'message' => 'Two-factor authentication is enabled. Password required.',
                        'hint' => $hint,
                        'has_recovery' => false,
                    ];
                }
                // Handle PHONE_NUMBER_UNOCCUPIED (signup required)
                if ($e->rpc === 'PHONE_NUMBER_UNOCCUPIED') {
                    return [
                        'type' => 'signup_required',
                        'message' => 'Account signup required. Please use Telegram app to complete signup.'
                    ];
                }
                // Re-throw other RPC errors
                throw $e;
            }
            
            // Handle special cases (signup required, etc.)
            // These are handled by completePhoneLogin() but we need to handle them manually
            if (isset($authorization['_'])) {
                if ($authorization['_'] === 'auth.authorizationSignUpRequired' || 
                    $authorization['_'] === 'account.needSignup') {
                    return [
                        'type' => 'signup_required',
                        'message' => 'Account signup required. Please use Telegram app to complete signup.',
                        'authorization' => $authorization
                    ];
                }
            }

            // If completePhoneLogin() succeeded, it means authentication was successful
            // The authorization object contains user info, so we can use it directly
            // Try to get user info from authorization or getSelf()
            $userInfo = null;
            
            // Check if authorization contains user info
            if (isset($authorization['user'])) {
                $userInfo = $authorization['user'];
            } elseif (isset($authorization['authorization']['user'])) {
                $userInfo = $authorization['authorization']['user'];
            } else {
                // Try to get user info via getSelf() if needed
                try {
                    if (!EventLoop::getDriver()) {
                        EventLoop::run(function () use (&$userInfo) {
                            $selfResult = $this->madeline->getSelf();
                            if ($selfResult instanceof \Amp\Future) {
                                $userInfo = await([$selfResult])[0];
                            } else {
                                $userInfo = $selfResult;
                            }
                        });
                    } else {
                        $selfResult = $this->madeline->getSelf();
                        if ($selfResult instanceof \Amp\Future) {
                            $userInfo = await([$selfResult])[0];
                        } else {
                            $userInfo = $selfResult;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Could not get user info via getSelf(): " . $e->getMessage());
                    // Continue without user info - authentication still succeeded
                }
            }

            // Authentication succeeded (completePhoneLogin() returned successfully)
            $this->connected = true;

            // Ensure session is saved (MadelineProto v8 auto-saves, but we can force it)
            // The session file is automatically updated when authentication completes
            // No explicit save needed in v8 - it's handled internally
            
            // Update config with authenticated user
            $config = $this->channelSource->config;
            $config['authenticated'] = true;
            if ($userInfo) {
                $config['user_id'] = $userInfo['id'] ?? $userInfo['user']['id'] ?? null;
                $config['username'] = $userInfo['username'] ?? $userInfo['user']['username'] ?? null;
            }
            $this->channelSource->update(['config' => $config]);
            
            // Log successful authentication
            Log::info("Authentication completed successfully", [
                'channel_id' => $this->channelSource->id,
                'session_file' => $this->sessionFile,
                'has_user_info' => !empty($userInfo),
                'user_id' => $config['user_id'] ?? null
            ]);

            return [
                'type' => 'success',
                'message' => 'Authentication successful',
                'user' => $userInfo ?? $authorization
            ];

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Handle specific error cases
            if (strpos($errorMessage, 'PHONE_CODE_INVALID') !== false) {
                return [
                    'type' => 'error',
                    'message' => 'Invalid verification code. Please try again.'
                ];
            }
            
            if (strpos($errorMessage, 'PHONE_CODE_EXPIRED') !== false) {
                return [
                    'type' => 'error',
                    'message' => 'Verification code expired. Please request a new code.'
                ];
            }

            return [
                'type' => 'error',
                'message' => 'Authentication failed: ' . $errorMessage
            ];
        }
    }

    /**
     * Complete password authentication (2FA).
     *
     * @param string $password
     * @return array
     */
    public function completePasswordAuth(string $password): array
    {
        // Use output buffering to prevent MadelineProto web UI output
        ob_start();
        ob_start();
        ob_start();
        
        try {
            // Ensure MadelineProto is initialized and started
            if (!$this->madeline) {
                // Initialize with proper settings
                $this->channelSource->refresh();
                $this->config = $this->channelSource->config ?? [];
                
                $apiId = $this->getConfig('api_id');
                $apiHash = $this->getConfig('api_hash');
                
                if (empty($apiId) || empty($apiHash)) {
                    while (ob_get_level() > 0) {
                        @ob_end_clean();
                    }
                    return [
                        'type' => 'error',
                        'message' => 'API ID and API Hash required'
                    ];
                }
                
                // Session file path
                if ($this->channelSource->isAdminOwned()) {
                    $this->sessionFile = storage_path('app/madelineproto/admin/' . $this->channelSource->id . '.madeline');
                } else {
                    $this->sessionFile = storage_path('app/madelineproto/' . $this->channelSource->id . '.madeline');
                }
                
                // Ensure directory exists
                $sessionDir = dirname($this->sessionFile);
                if (!is_dir($sessionDir)) {
                    mkdir($sessionDir, 0755, true);
                }
                
                // Settings for MadelineProto v8
                $appInfo = new \danog\MadelineProto\Settings\AppInfo();
                $appInfo->setApiId((int) $apiId);
                $appInfo->setApiHash($apiHash);
                
                $settings = new \danog\MadelineProto\Settings();
                $settings->setAppInfo($appInfo);
                
                // Disable internal logging
                $logger = new \danog\MadelineProto\Settings\Logger();
                $logger->setType(\danog\MadelineProto\Logger::CALLABLE_LOGGER);
                $logger->setExtra(function () {});
                $settings->setLogger($logger);
                
                // Initialize MadelineProto v8
                $this->madeline = new \danog\MadelineProto\API($this->sessionFile, $settings);
            }
            
            // Ensure start() is called to initialize session (but suppress output)
            // This is required before complete2faLogin() can work
            if (!$this->connected) {
                try {
                    $sessionExists = file_exists($this->sessionFile);
                    if ($sessionExists) {
                        // Start MadelineProto but suppress output
                        if (!EventLoop::getDriver()) {
                            EventLoop::run(function () {
                                $startResult = $this->madeline->start();
                                if ($startResult instanceof \Amp\Future) {
                                    await([$startResult])[0];
                                }
                            });
                        } else {
                            $startResult = $this->madeline->start();
                            if ($startResult instanceof \Amp\Future) {
                                await([$startResult])[0];
                            }
                        }
                        
                        // Clear any output
                        while (ob_get_level() > 0) {
                            @ob_get_clean();
                        }
                    }
                } catch (\Exception $e) {
                    // If start fails, continue anyway - might need password
                    Log::debug("start() failed in completePasswordAuth (expected if password needed): " . $e->getMessage());
                }
            }

            if (!$this->madeline) {
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                return [
                    'type' => 'error',
                    'message' => 'MadelineProto not initialized. Please start authentication first.'
                ];
            }

            // Set programmatic auth mode to prevent web UI
            putenv('MADELINE_PROGRAMMATIC_AUTH=1');
            $_ENV['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            $_SERVER['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            $_POST['type'] = 'password';
            $_POST['password'] = $password;

            // Ensure authorization state is set to WAITING_PASSWORD
            // This is required before calling complete2faLogin()
            // The authorization state should already be set from completePhoneLogin()
            // when it detected password requirement
            try {
                // Set authorization state to WAITING_PASSWORD if not already set
                if (!isset($this->madeline->authorized) || 
                    $this->madeline->authorized !== \danog\MadelineProto\API::WAITING_PASSWORD) {
                    $this->madeline->authorized = \danog\MadelineProto\API::WAITING_PASSWORD;
                }
                
                // Ensure authorization object exists
                if (!isset($this->madeline->authorization) || empty($this->madeline->authorization)) {
                    // Get hint from config if available
                    $hint = $this->channelSource->config['password_hint'] ?? '';
                    $this->madeline->authorization = [
                        'hint' => $hint,
                        '_' => 'account.password'
                    ];
                }
            } catch (\Exception $e) {
                // If setting state fails, try to continue anyway
                Log::warning("Could not set password authorization state: " . $e->getMessage());
            }

            // Complete 2FA login with password
            $authorization = null;
            try {
                if (!EventLoop::getDriver()) {
                    EventLoop::run(function () use ($password, &$authorization) {
                        $authorization = $this->madeline->complete2faLogin($password);
                    });
                } else {
                    $future = async(function () use ($password) {
                        return $this->madeline->complete2faLogin($password);
                    });
                    $authorization = await([$future])[0];
                }
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                // Clear output buffers first
                $output = '';
                while (ob_get_level() > 0) {
                    $output .= ob_get_clean();
                }
                
                if ($e->rpc === 'PASSWORD_HASH_INVALID') {
                    return [
                        'type' => 'error',
                        'message' => 'Invalid password. Please try again.'
                    ];
                }
                
                // Check output for password errors even if exception doesn't match
                if (!empty($output) && strpos($output, 'PASSWORD_HASH_INVALID') !== false) {
                    return [
                        'type' => 'error',
                        'message' => 'Invalid password. Please try again.'
                    ];
                }
                
                throw $e;
            } catch (\Exception $e) {
                // Clear output buffers
                $output = '';
                while (ob_get_level() > 0) {
                    $output .= ob_get_clean();
                }
                
                // Check if output contains password error
                if (!empty($output) && (strpos($output, 'PASSWORD_HASH_INVALID') !== false || 
                    strpos($output, 'ERROR: PASSWORD_HASH_INVALID') !== false)) {
                    return [
                        'type' => 'error',
                        'message' => 'Invalid password. Please try again.'
                    ];
                }
                
                if (strpos($e->getMessage(), 'PASSWORD_HASH_INVALID') !== false) {
                    return [
                        'type' => 'error',
                        'message' => 'Invalid password. Please try again.'
                    ];
                }
                
                throw $e;
            }
            
            // Clear any remaining output buffers AFTER successful authentication
            $output = '';
            while (ob_get_level() > 0) {
                $output .= ob_get_clean();
            }
            
            // Log if web UI was suppressed (shouldn't happen on success)
            if (!empty($output) && (strpos($output, '<html') !== false || strpos($output, 'MadelineProto') !== false)) {
                Log::warning("MadelineProto web UI output suppressed in completePasswordAuth (after success)", [
                    'output_length' => strlen($output),
                    'contains_password_error' => strpos($output, 'PASSWORD_HASH_INVALID') !== false
                ]);
            }

            // Get user info
            $userInfo = null;
            try {
                if (!EventLoop::getDriver()) {
                    EventLoop::run(function () use (&$userInfo) {
                        $selfResult = $this->madeline->getSelf();
                        if ($selfResult instanceof \Amp\Future) {
                            $userInfo = await([$selfResult])[0];
                        } else {
                            $userInfo = $selfResult;
                        }
                    });
                } else {
                    $selfResult = $this->madeline->getSelf();
                    if ($selfResult instanceof \Amp\Future) {
                        $userInfo = await([$selfResult])[0];
                    } else {
                        $userInfo = $selfResult;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Could not get user info via getSelf(): " . $e->getMessage());
            }

            $this->connected = true;

            // Update config
            $config = $this->channelSource->config;
            $config['authenticated'] = true;
            unset($config['password_required']); // Remove password requirement flag
            if ($userInfo) {
                $config['user_id'] = $userInfo['id'] ?? $userInfo['user']['id'] ?? null;
                $config['username'] = $userInfo['username'] ?? $userInfo['user']['username'] ?? null;
            }
            $this->channelSource->update(['config' => $config]);

            Log::info("Password authentication completed successfully", [
                'channel_id' => $this->channelSource->id,
            ]);

            return [
                'type' => 'success',
                'message' => 'Authentication completed successfully',
                'user' => $userInfo
            ];
        } catch (\Exception $e) {
            // Final buffer clear on any exception
            $output = '';
            while (ob_get_level() > 0) {
                $output .= ob_get_clean();
            }
            
            // Check if HTML was output (password error)
            if (!empty($output) && (strpos($output, 'PASSWORD_HASH_INVALID') !== false || 
                strpos($output, 'ERROR: PASSWORD_HASH_INVALID') !== false)) {
                return [
                    'type' => 'error',
                    'message' => 'Invalid password. Please try again.'
                ];
            }
            
            $this->logError("Password authentication failed: " . $e->getMessage());
            return [
                'type' => 'error',
                'message' => 'Password authentication failed: ' . $e->getMessage()
            ];
        } finally {
            // Ensure all buffers are cleared in finally block
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
        }
    }

    /**
     * Get list of dialogs (chats, channels, groups).
     *
     * @return array
     */
}
