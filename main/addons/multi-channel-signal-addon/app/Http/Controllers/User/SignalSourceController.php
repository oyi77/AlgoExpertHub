<?php

namespace Addons\MultiChannelSignalAddon\App\Http\Controllers\User;

use Addons\MultiChannelSignalAddon\App\Adapters\ApiAdapter;
use Addons\MultiChannelSignalAddon\App\Adapters\RssAdapter;
use Addons\MultiChannelSignalAddon\App\Adapters\TelegramAdapter;
use Addons\MultiChannelSignalAddon\App\Adapters\TelegramMtprotoAdapter;
use Addons\MultiChannelSignalAddon\App\Adapters\TradingBotAdapter;
use Addons\MultiChannelSignalAddon\App\Adapters\WebScrapeAdapter;
use Addons\MultiChannelSignalAddon\App\Http\Controllers\Controller;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Addons\MultiChannelSignalAddon\App\Services\TelegramChannelService;
use Addons\MultiChannelSignalAddon\App\Services\TelegramMtprotoService;
use App\Helpers\NotificationHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Signal Source Controller - User
 * Manages user's own signal source connections only
 */
class SignalSourceController extends Controller
{
    public function __construct(
        protected TelegramChannelService $telegramService,
        protected TelegramMtprotoService $telegramMtprotoService
    ) {
    }

    /**
     * Display user's signal sources.
     */
    public function index(Request $request): View
    {
        $data['title'] = 'My Signal Sources';

        $query = ChannelSource::userOwned()
            ->where('user_id', Auth::id())
            ->with(['defaultPlan', 'defaultMarket', 'defaultTimeframe']);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $data['sources'] = $query->latest()->paginate(20);
        $data['stats'] = [
            'total' => ChannelSource::where('user_id', Auth::id())->count(),
            'active' => ChannelSource::where('user_id', Auth::id())->where('status', 'active')->count(),
            'paused' => ChannelSource::where('user_id', Auth::id())->where('status', 'paused')->count(),
            'error' => ChannelSource::where('user_id', Auth::id())->where('status', 'error')->count(),
        ];

        return view('multi-channel-signal-addon::user.signal-source.index', $data);
    }

    /**
     * Show form to create a new signal source.
     */
    public function create(string $type = 'telegram'): View
    {
        $allowedTypes = ['telegram', 'telegram_mtproto', 'api', 'web_scrape', 'rss', 'trading_bot'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'telegram';
        }

        $data['title'] = 'Add Signal Source';
        $data['type'] = $type;

        return view('multi-channel-signal-addon::user.signal-source.create', $data);
    }

    /**
     * Store a new signal source.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:telegram,telegram_mtproto,api,web_scrape,rss,trading_bot',
        ]);

        $payload = $request->all();
        $payload['user_id'] = Auth::id();

        try {
            $result = match ($payload['type']) {
                'telegram' => $this->storeTelegramSource($payload),
                'telegram_mtproto' => $this->storeTelegramMtprotoSource($payload),
                'api' => $this->storeApiSource($payload),
                'web_scrape' => $this->storeWebScrapeSource($payload),
                'rss' => $this->storeRssSource($payload),
                'trading_bot' => $this->storeTradingBotSource($payload),
                default => ['type' => 'error', 'message' => 'Invalid source type'],
            };

            if (in_array($result['type'], ['success', 'warning'], true)) {
                return redirect()->route('user.signal-sources.index')
                    ->with('notify', NotificationHelper::success($result['message'], 'Success'));
            }

            if (in_array($result['type'], ['phone_required', 'code_required'], true)) {
                return redirect()->route('user.signal-sources.authenticate', [
                    'id' => $result['channel_source']->id,
                    'step' => $result['step'] ?? 'phone',
                ])->with('notify', NotificationHelper::info($result['message'], 'Info'));
            }

            return redirect()->back()->with('notify', NotificationHelper::error($result['message'] ?? 'Failed to create source', 'Error'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('notify', NotificationHelper::error('Error: ' . $th->getMessage(), 'Error'));
        }
    }

    protected function storeTelegramSource(array $data): array
    {
        return $this->telegramService->createChannel($data);
    }

    protected function storeTelegramMtprotoSource(array $data): array
    {
        return $this->telegramMtprotoService->createChannel($data);
    }

    protected function storeApiSource(array $data): array
    {
        $adapter = new \Addons\MultiChannelSignalAddon\App\Adapters\ApiAdapter(
            new ChannelSource(['user_id' => $data['user_id']])
        );

        $config = [
            'webhook_url' => $data['webhook_url'] ?? null,
            'secret_key' => $data['secret_key'] ?? null,
        ];

        if (!$adapter->validateConfig($config)) {
            return ['type' => 'error', 'message' => 'Invalid configuration'];
        }

        $source = ChannelSource::create([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'type' => 'api',
            'config' => $config,
            'status' => 'active',
        ]);

        if (empty($config['webhook_url'])) {
            $webhookUrl = $adapter->generateWebhookUrl();
            $config['webhook_url'] = $webhookUrl;
            $source->update(['config' => $config]);

            return [
                'type' => 'success',
                'message' => 'API source created. Use this webhook URL: ' . $webhookUrl,
                'channel_source' => $source,
            ];
        }

        return [
            'type' => 'success',
            'message' => 'API source created successfully',
            'channel_source' => $source,
        ];
    }

    protected function storeWebScrapeSource(array $data): array
    {
        $source = new ChannelSource([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'type' => 'web_scrape',
            'config' => [
                'url' => $data['url'],
                'selector' => $data['selector'],
                'selector_type' => $data['selector_type'] ?? 'css',
            ],
            'status' => 'active',
        ]);

        $adapter = new \Addons\MultiChannelSignalAddon\App\Adapters\WebScrapeAdapter($source);
        if (!$adapter->validateConfig($source->config)) {
            return ['type' => 'error', 'message' => 'Invalid URL or selector'];
        }

        $source->save();

        if (!$adapter->connect($source)) {
            $source->update(['status' => 'error']);
            return ['type' => 'error', 'message' => 'Failed to connect to URL.'];
        }

        return [
            'type' => 'success',
            'message' => 'Web scraping source created successfully',
            'channel_source' => $source,
        ];
    }

    protected function storeRssSource(array $data): array
    {
        $source = new ChannelSource([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'type' => 'rss',
            'config' => ['feed_url' => $data['feed_url']],
            'status' => 'active',
        ]);

        $adapter = new \Addons\MultiChannelSignalAddon\App\Adapters\RssAdapter($source);
        if (!$adapter->validateConfig($source->config)) {
            return ['type' => 'error', 'message' => 'Invalid feed URL'];
        }

        $source->save();

        if (!$adapter->connect($source)) {
            $source->update(['status' => 'error']);
            return ['type' => 'error', 'message' => 'Failed to validate RSS feed.'];
        }

        return [
            'type' => 'success',
            'message' => 'RSS source created successfully',
            'channel_source' => $source,
        ];
    }

    protected function storeTradingBotSource(array $data): array
    {
        $config = [
            'source_type' => $data['source_type'] ?? 'api', // 'api' or 'firebase'
            'api_endpoint' => $data['api_endpoint'] ?? null,
            'api_token' => $data['api_token'] ?? null,
            'auth_type' => $data['auth_type'] ?? 'Bearer',
            'require_auth' => isset($data['require_auth']) ? (bool)$data['require_auth'] : false,
            'firebase_project_id' => $data['firebase_project_id'] ?? null,
            'firebase_credentials' => $data['firebase_credentials'] ?? null,
            'firebase_collection' => $data['firebase_collection'] ?? 'signals',
        ];

        $source = new ChannelSource([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'type' => 'trading_bot',
            'config' => $config,
            'status' => 'active',
        ]);

        $adapter = new \Addons\MultiChannelSignalAddon\App\Adapters\TradingBotAdapter($source);
        if (!$adapter->validateConfig($config)) {
            return ['type' => 'error', 'message' => 'Invalid configuration. Please provide either API endpoint or Firebase credentials.'];
        }

        $source->save();

        if (!$adapter->connect($source)) {
            $source->update(['status' => 'error']);
            return ['type' => 'error', 'message' => 'Failed to connect to Trading Bot source.'];
        }

        return [
            'type' => 'success',
            'message' => 'Trading Bot source created successfully',
            'channel_source' => $source,
        ];
    }

    /**
     * Handle authentication (for Telegram MTProto).
     */
    public function authenticate(int $id, Request $request): View|RedirectResponse
    {
        $source = ChannelSource::where('user_id', Auth::id())
            ->where('type', 'telegram_mtproto')
            ->findOrFail($id);

        // Use the same authentication logic as the old ChannelController
        // (Copy from User/ChannelController::authenticate)
        $step = $request->get('step', 'phone');
        $data = [
            'title' => 'Authenticate Telegram Account',
            'source' => $source,
            'step' => $step,
        ];

        if ($request->isMethod('post') && $step === 'phone') {
            $request->validate(['phone_number' => 'required|string']);

            // Get current config and update phone number
            $config = $source->config ?? [];
            $config['phone_number'] = $request->phone_number;
            
            // Use forceFill and save to ensure config is properly saved
            $source->forceFill(['config' => $config]);
            $source->save();
            
            // Refresh to get the latest data from database
            $source->refresh();
            
            // Log to verify phone number was saved
            \Log::info("Phone number saved (user)", [
                'source_id' => $source->id,
                'phone_number' => $request->phone_number,
                'config_after_save' => $source->config ?? [],
                'phone_in_config' => $source->config['phone_number'] ?? 'NOT FOUND'
            ]);

            // CRITICAL: Set programmatic auth mode BEFORE initializing adapter
            // This prevents MadelineProto from outputting web UI
            putenv('MADELINE_PROGRAMMATIC_AUTH=1');
            $_ENV['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            $_SERVER['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            
            // Store original POST data but DON'T clear it here - CSRF validation needs it
            $originalPost = $_POST ?? [];
            // Only set 'type' to prevent webEcho() from being called
            $_POST['type'] = 'phone';
            // DO NOT set $_POST['phone_number'] - it triggers webPhoneLogin()
            
            // Use output buffering to catch any output
            ob_start();
            ob_start();
            ob_start();
            
            $authResult = null;
            try {
                $adapter = new \Addons\MultiChannelSignalAddon\App\Adapters\TelegramMtprotoAdapter($source);
                $authResult = $adapter->startAuth();
                
                // Log the result for debugging
                \Log::info("startAuth() result (user)", [
                    'type' => $authResult['type'] ?? 'unknown',
                    'message' => $authResult['message'] ?? 'no message',
                    'has_phone_code_hash' => isset($authResult['phone_code_hash'])
                ]);
            } catch (\Exception $e) {
                // Clear buffers on exception
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                \Log::error("startAuth() exception (user)", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->route('user.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'phone',
                ])->with('notify', NotificationHelper::error('Authentication error: ' . $e->getMessage(), 'Error'));
            }
            
            // Get any captured output
            $output = '';
            while (ob_get_level() > 0) {
                $output .= ob_get_clean();
            }
            
            // Log if there was any output (might be MadelineProto web UI)
            if (!empty($output)) {
                \Log::warning("Output captured during startAuth() (user)", [
                    'output_length' => strlen($output),
                    'output_preview' => substr($output, 0, 500),
                    'contains_html' => strpos($output, '<html') !== false,
                    'contains_madeline' => strpos($output, 'MadelineProto') !== false
                ]);
            }
            
            // Ensure we have a valid result
            if (!is_array($authResult) || !isset($authResult['type'])) {
                \Log::error("Invalid startAuth() result (user)", ['result' => $authResult]);
                return redirect()->route('user.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'phone',
                ])->with('notify', NotificationHelper::error('Invalid authentication response. Please try again.', 'Error'));
            }

            if ($authResult['type'] === 'code_required') {
                $_POST = $originalPost;
                
                $phoneCodeHash = $authResult['phone_code_hash'] ?? null;
                if (!$phoneCodeHash) {
                    \Log::error("code_required but no phone_code_hash (user)", ['result' => $authResult]);
                    return redirect()->route('user.signal-sources.authenticate', [
                        'id' => $source->id,
                        'step' => 'phone',
                    ])->with('notify', NotificationHelper::error('Failed to get verification code hash. Please try again.', 'Error'));
                }
                
                $request->session()->put('phone_code_hash', $phoneCodeHash);
                // CRITICAL: Save session before redirect
                $request->session()->save();

                return redirect()->route('user.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'code',
                ])->with('notify', NotificationHelper::info($authResult['message'] ?? 'Verification code sent. Please check your Telegram app.', 'Info'));
            }

            if ($authResult['type'] === 'password_required') {
                $_POST = $originalPost;
                
                $phoneCodeHash = $authResult['phone_code_hash'] ?? null;
                if ($phoneCodeHash) {
                    $request->session()->put('phone_code_hash', $phoneCodeHash);
                    $request->session()->save();
                }
                
                return redirect()->route('user.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'password',
                ])->with('notify', NotificationHelper::info($authResult['message'] ?? 'Two-factor authentication is enabled. Please enter your password.', 'Info'));
            }

            if ($authResult['type'] === 'error') {
                $_POST = $originalPost;
                
                $errorMessage = $authResult['message'] ?? 'Failed to send verification code. Please check your API credentials and phone number format.';
                if (strpos($errorMessage, 'api_id') !== false || strpos($errorMessage, 'api_hash') !== false) {
                    $errorMessage = 'Invalid API credentials. Please check your API ID and API Hash in the source configuration.';
                } elseif (strpos($errorMessage, 'phone') !== false) {
                    $errorMessage = 'Invalid phone number format. Please use international format (e.g., +1234567890).';
                }
                
                return redirect()->route('user.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'phone',
                ])->with('notify', NotificationHelper::error($errorMessage, 'Error'));
            }

            if ($authResult['type'] === 'success') {
                $_POST = $originalPost;
                return redirect()->route('user.signal-sources.index')
                    ->with('notify', NotificationHelper::success('Telegram account authenticated successfully!', 'Success'));
            }
            
            if ($authResult['type'] === 'phone_required') {
                $_POST = $originalPost;
                // This shouldn't happen since we already have phone_number, but handle it anyway
                return redirect()->route('user.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'phone',
                ])->with('notify', NotificationHelper::error('Phone number is required.', 'Error'));
            }
            
            // Fallback for any other unexpected result type
            \Log::warning("Unexpected startAuth() result type (user)", [
                'type' => $authResult['type'],
                'result' => $authResult
            ]);
            $_POST = $originalPost;
            return redirect()->route('user.signal-sources.authenticate', [
                'id' => $source->id,
                'step' => 'phone',
            ])->with('notify', NotificationHelper::error('Unexpected authentication response. Please try again.', 'Error'));
        }

        if ($request->isMethod('post') && $step === 'code') {
            // CRITICAL: Save session before any operations to prevent CSRF token expiration
            $request->session()->save();
            
            $request->validate(['code' => 'required|string']);

            $phoneCodeHash = $request->session()->get('phone_code_hash') ?? $request->phone_code_hash;
            if (!$phoneCodeHash) {
                // Redirect to phone step instead of back
                $request->session()->save(); // Save session before redirect
                return redirect()->route('user.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'phone',
                ])->with('notify', NotificationHelper::error('Invalid session. Please start over.', 'Error'));
            }

            // CRITICAL: Refresh channel from database to get latest config (including phone_number)
            // The channel instance might be stale if it was loaded before phone number was saved
            $source = $source->fresh();
            
            // Verify phone number exists before proceeding
            if (empty($source->config['phone_number'] ?? null)) {
                \Log::error("Phone number missing after refresh (user)", [
                    'source_id' => $source->id,
                    'config' => $source->config
                ]);
                return redirect()->route('user.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'phone',
                ])->with('notify', NotificationHelper::error('Phone number not found. Please re-enter your phone number.', 'Error'));
            }

            // Prevent MadelineProto web UI
            $_ENV['MADELINE_PROGRAMMATIC_AUTH'] = true;
            // Store original POST data
            $originalPost = $_POST ?? [];
            
            // Suppress any output from MadelineProto
            ob_start();
            ob_start();
            $result = $this->telegramMtprotoService->completeAuth($source, $request->code, $phoneCodeHash);
            $output = ob_get_clean();
            ob_end_clean();
            
            // Log if web UI was suppressed
            if (!empty($output) && (strpos($output, '<html') !== false || strpos($output, 'MadelineProto') !== false)) {
                \Log::warning("MadelineProto web UI output suppressed in completeAuth (user)", ['output_length' => strlen($output)]);
            }

            if ($result['type'] === 'success') {
                // Clear buffers before redirect
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                $_POST = $originalPost;
                
                $request->session()->forget('phone_code_hash');
                // CRITICAL: Save session and regenerate CSRF token before redirect
                $request->session()->save();
                $request->session()->regenerateToken(); // Regenerate CSRF token

                return redirect()->route('user.signal-sources.index')
                    ->with('notify', NotificationHelper::success('Telegram account authenticated successfully!', 'Success'));
            }

            if ($result['type'] === 'password_required') {
                // Clear buffers before redirect
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                $_POST = $originalPost;
                
                $request->session()->put('phone_code_hash', $phoneCodeHash);
                $request->session()->save();
                
                return redirect()->route('user.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'password',
                ])->with('notify', NotificationHelper::info($result['message'] ?? 'Two-factor authentication is enabled. Please enter your password.', 'Info'));
            }

            // Clear buffers before redirect
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $_POST = $originalPost;
            
            $errorMessage = $result['message'] ?? 'Verification failed. Please try again.';
            if (strpos($errorMessage, 'PHONE_CODE_INVALID') !== false) {
                $errorMessage = 'Invalid verification code. Please check and try again.';
            } elseif (strpos($errorMessage, 'PHONE_CODE_EXPIRED') !== false) {
                $errorMessage = 'Verification code has expired. Please request a new code.';
            }
            
            return redirect()->back()->with('notify', NotificationHelper::error($errorMessage, 'Error'));
        }

        // Handle password step (2FA)
        if ($request->isMethod('post') && $step === 'password') {
            $request->session()->save();
            $request->validate(['password' => 'required|string']);

            $source = $source->fresh();

            // Set programmatic auth mode
            putenv('MADELINE_PROGRAMMATIC_AUTH=1');
            $_ENV['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            $_SERVER['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            $originalPost = $_POST ?? [];

            // Suppress output
            ob_start();
            ob_start();
            ob_start();
            
            $result = $this->telegramMtprotoService->completePasswordAuth($source, $request->password);
            
            $output = '';
            while (ob_get_level() > 0) {
                $output .= ob_get_clean();
            }
            
            if (!empty($output) && (strpos($output, '<html') !== false || strpos($output, 'MadelineProto') !== false)) {
                \Log::warning("MadelineProto HTML output captured in password auth (user)", [
                    'output_length' => strlen($output)
                ]);
            }

            if ($result['type'] === 'success') {
                $_POST = $originalPost;
                $request->session()->save();
                $request->session()->regenerateToken();

                return redirect()->route('user.signal-sources.index')
                    ->with('notify', NotificationHelper::success('Telegram account authenticated successfully!', 'Success'));
            }

            $_POST = $originalPost;
            
            $errorMessage = $result['message'] ?? 'Password authentication failed.';
            if (strpos($errorMessage, 'Invalid password') !== false && !empty($source->config['password_hint'] ?? '')) {
                $errorMessage .= ' Hint: ' . $source->config['password_hint'];
            }
            
            return redirect()->back()->with('notify', NotificationHelper::error($errorMessage, 'Error'))->withInput();
        }

        return view('multi-channel-signal-addon::user.signal-source.authenticate', $data);
    }

    /**
     * Update source status.
     */
    public function updateStatus(Request $request, int $id)
    {
        try {
            $source = ChannelSource::where('user_id', Auth::id())->findOrFail($id);

            $validated = $request->validate([
                'status' => 'required|in:active,paused'
            ]);

            $status = $validated['status'];
            $source->update(['status' => $status]);

            $message = $status === 'active' ? 'Source resumed' : 'Source paused';
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return redirect()->back()->with('notify', NotificationHelper::success($message, 'Success'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Failed to update signal source status', [
                'source_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update status: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('notify', NotificationHelper::error('Failed to update status: ' . $e->getMessage(), 'Error'));
        }
    }

    /**
     * Test connection for a signal source.
     */
    public function testConnection(int $id): JsonResponse
    {
        try {
            $source = ChannelSource::where('user_id', Auth::id())->findOrFail($id);
            $adapter = $this->getAdapter($source);

            if (!$adapter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unsupported source type: ' . $source->type,
                ], 400);
            }

            // Validate config first
            if (!$adapter->validateConfig($source->config ?? [])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid configuration. Please check your settings.',
                ], 400);
            }

            // Test connection
            $connected = $adapter->connect($source);

            if ($connected) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful!',
                    'details' => $this->getConnectionDetails($source, $adapter),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Connection failed. Please check your credentials and try again.',
            ], 400);

        } catch (\Exception $e) {
            Log::error("Test connection failed: " . $e->getMessage(), [
                'source_id' => $id,
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error testing connection: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get adapter instance for source type.
     */
    protected function getAdapter(ChannelSource $source)
    {
        return match ($source->type) {
            'telegram' => new TelegramAdapter($source),
            'telegram_mtproto' => new TelegramMtprotoAdapter($source),
            'api' => new ApiAdapter($source),
            'web_scrape' => new WebScrapeAdapter($source),
            'rss' => new RssAdapter($source),
            'trading_bot' => new TradingBotAdapter($source),
            default => null,
        };
    }

    /**
     * Get connection details for response.
     */
    protected function getConnectionDetails(ChannelSource $source, $adapter): array
    {
        $details = [
            'type' => $source->type,
            'status' => $source->status,
        ];

        switch ($source->type) {
            case 'telegram':
                $details['bot_info'] = 'Bot is connected and ready';
                break;
            case 'telegram_mtproto':
                $details['account_info'] = 'Telegram account authenticated';
                break;
            case 'api':
                $details['webhook_url'] = $source->config['webhook_url'] ?? 'Not configured';
                break;
            case 'web_scrape':
                $details['url'] = $source->config['url'] ?? 'Not configured';
                break;
            case 'rss':
                $details['feed_url'] = $source->config['feed_url'] ?? 'Not configured';
                break;
            case 'trading_bot':
                if (!empty($source->config['firebase_project_id'])) {
                    $details['source'] = 'Firebase';
                    $details['project_id'] = $source->config['firebase_project_id'];
                } elseif (!empty($source->config['api_endpoint'])) {
                    $details['source'] = 'API';
                    $details['endpoint'] = $source->config['api_endpoint'];
                } else {
                    $details['source'] = 'Not configured';
                }
                break;
        }

        return $details;
    }

    /**
     * Delete signal source.
     */
    public function destroy(int $id): RedirectResponse
    {
        $source = ChannelSource::where('user_id', Auth::id())->findOrFail($id);

        // Cleanup
        if ($source->type === 'telegram') {
            $adapter = new TelegramAdapter($source);
            $adapter->removeWebhook();
        } elseif ($source->type === 'telegram_mtproto') {
            $sessionFile = storage_path('app/madelineproto/' . $source->id . '.madeline');
            if (file_exists($sessionFile)) {
                @unlink($sessionFile);
            }
        }

        $source->delete();

        return redirect()->route('user.signal-sources.index')
            ->with('notify', NotificationHelper::success('Signal source deleted successfully', 'Success'));
    }
}

