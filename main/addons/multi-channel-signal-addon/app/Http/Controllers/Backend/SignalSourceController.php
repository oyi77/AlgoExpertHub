<?php

namespace Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend;

use Addons\MultiChannelSignalAddon\App\Adapters\ApiAdapter;
use Addons\MultiChannelSignalAddon\App\Adapters\RssAdapter;
use Addons\MultiChannelSignalAddon\App\Adapters\TelegramAdapter;
use Addons\MultiChannelSignalAddon\App\Adapters\TelegramMtprotoAdapter;
use Addons\MultiChannelSignalAddon\App\Adapters\TradingBotAdapter;
use Addons\MultiChannelSignalAddon\App\Adapters\WebScrapeAdapter;
use Addons\MultiChannelSignalAddon\App\Http\Controllers\Controller;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Addons\MultiChannelSignalAddon\App\Services\TelegramMtprotoService;
use App\Helpers\Helper\Helper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

/**
 * Signal Source Controller - Admin
 * Manages signal source connections only (Telegram, API, Web Scrape, RSS)
 * Does NOT handle channel forwarding/assignment
 */
class SignalSourceController extends Controller
{
    protected TelegramMtprotoService $telegramMtprotoService;

    public function __construct(TelegramMtprotoService $telegramMtprotoService)
    {
        $this->telegramMtprotoService = $telegramMtprotoService;
    }

    /**
     * Display all signal sources (admin + user connections).
     */
    public function index(Request $request): View
    {
        $data['title'] = 'Signal Sources';

        // Admin can see all sources (admin-owned + user-owned)
        $query = ChannelSource::with(['user', 'defaultPlan', 'defaultMarket', 'defaultTimeframe']);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->ownership) {
            if ($request->ownership === 'admin') {
                $query->adminOwned();
            } elseif ($request->ownership === 'user') {
                $query->userOwned();
            }
        }

        $sources = $query->latest()->paginate(Helper::pagination());

        $data['sources'] = $sources;
        $data['stats'] = [
            'total' => ChannelSource::count(),
            'admin' => ChannelSource::adminOwned()->count(),
            'user' => ChannelSource::userOwned()->count(),
            'active' => ChannelSource::where('status', 'active')->count(),
            'paused' => ChannelSource::where('status', 'paused')->count(),
            'error' => ChannelSource::where('status', 'error')->count(),
        ];

        return view('multi-channel-signal-addon::backend.signal-source.index', $data);
    }

    /**
     * Show form to create a new signal source.
     */
    public function create(string $type = 'telegram_mtproto'): View
    {
        $allowedTypes = ['telegram_mtproto', 'telegram', 'api', 'web_scrape', 'rss', 'trading_bot'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'telegram_mtproto';
        }

        $data['title'] = 'Create Signal Source';
        $data['type'] = $type;
        $data['madelineproto_installed'] = class_exists('\danog\MadelineProto\API');

        return view('multi-channel-signal-addon::backend.signal-source.create', $data);
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
        $payload['is_admin_owned'] = true;
        $payload['user_id'] = null;

        try {
            if ($payload['type'] === 'telegram_mtproto') {
                return $this->storeTelegramMtprotoSource($payload, $request);
            }

            // For other types, create directly
            $source = ChannelSource::create([
                'user_id' => null,
                'is_admin_owned' => true,
                'name' => $payload['name'],
                'type' => $payload['type'],
                'config' => $this->buildConfig($payload),
                'status' => 'active',
            ]);

            return redirect()->route('admin.signal-sources.index')
                ->with('success', 'Signal source created successfully');
        } catch (\Throwable $th) {
            return redirect()->route('admin.signal-sources.index')
                ->with('error', 'Error: ' . $th->getMessage());
        }
    }

    /**
     * Store Telegram MTProto source.
     */
    protected function storeTelegramMtprotoSource(array $data, Request $request): RedirectResponse
    {
        $request->validate([
            'api_id' => 'required|string',
            'api_hash' => 'required|string',
        ]);

        if (!empty($data['bot_token'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'MadelineProto uses USER authentication with phone number, NOT bot token.');
        }

        $result = $this->telegramMtprotoService->createChannel([
            'user_id' => null,
            'name' => $data['name'],
            'api_id' => $data['api_id'],
            'api_hash' => $data['api_hash'],
            'phone_number' => $data['phone_number'] ?? null,
        ]);

        if ($result['type'] === 'success') {
            $source = $result['channel_source'];
            if (!$source->is_admin_owned) {
                $source->update(['is_admin_owned' => true, 'user_id' => null]);
            }

            return redirect()->route('admin.signal-sources.index')
                ->with('success', 'Telegram MTProto source created successfully');
        }

        if (in_array($result['type'], ['phone_required', 'code_required'], true)) {
            if (!isset($result['channel_source']) || !$result['channel_source']) {
                return redirect()->route('admin.signal-sources.index')
                    ->with('error', 'Failed to create source. Please try again.');
            }

            $source = $result['channel_source'];
            if ($source->type !== 'telegram_mtproto') {
                return redirect()->route('admin.signal-sources.index')
                    ->with('error', 'Invalid source type for authentication.');
            }

            return redirect()->route('admin.signal-sources.authenticate', [
                'id' => $source->id,
                'step' => $result['step'] ?? 'phone',
            ])->with('info', $result['message']);
        }

        return redirect()->route('admin.signal-sources.index')
            ->with('error', $result['message'] ?? 'Failed to create source');
    }

    /**
     * Show edit form.
     */
    public function edit(int $id): View
    {
        $source = ChannelSource::findOrFail($id);

        $data['title'] = 'Edit Signal Source';
        $data['source'] = $source;

        return view('multi-channel-signal-addon::backend.signal-source.edit', $data);
    }

    /**
     * Update signal source.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $source = ChannelSource::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'parser_preference' => 'nullable|in:auto,pattern,ai',
        ]);

        // For telegram_mtproto, update API credentials if provided
        if ($source->type === 'telegram_mtproto') {
            $request->validate([
                'api_id' => 'required|string',
                'api_hash' => 'required|string',
            ]);

            $config = $source->config;
            $config['api_id'] = $request->api_id;
            $config['api_hash'] = $request->api_hash;

            // If API credentials changed, delete old session file
            if (($config['api_id'] !== ($source->config['api_id'] ?? '')) ||
                ($config['api_hash'] !== ($source->config['api_hash'] ?? ''))) {
                $sessionFile = storage_path('app/madelineproto/admin/' . $source->id . '.madeline');
                if (file_exists($sessionFile)) {
                    @unlink($sessionFile);
                }
                $source->update(['status' => 'pending']);
            }

            $source->update(['config' => $config]);
        }

        $source->update($request->only(['name', 'parser_preference']));

        return redirect()->route('admin.signal-sources.index')
            ->with('success', 'Signal source updated successfully');
    }

    /**
     * Handle MTProto authentication.
     */
    public function authenticate(int $id, Request $request): View|RedirectResponse
    {
        ob_start();
        ob_start();

        $source = ChannelSource::findOrFail($id);

        if ($source->type !== 'telegram_mtproto') {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            return redirect()->route('admin.signal-sources.index')
                ->with('error', 'This source type does not require user authentication.');
        }

        $originalPost = $_POST ?? [];
        $step = $request->get('step', 'phone');

        $data = [
            'title' => 'Authenticate Telegram Account',
            'source' => $source,
            'step' => $step,
            'password_hint' => $request->session()->get('admin_password_hint', $source->config['password_hint'] ?? ''),
        ];

        if ($request->isMethod('post') && $step === 'phone') {
            $request->validate(['phone_number' => 'required|string']);

            $config = $source->config ?? [];
            $config['phone_number'] = $request->phone_number;

            $source->forceFill(['config' => $config]);
            $source->save();
            $source->refresh();

            Log::info("Phone number saved", [
                'source_id' => $source->id,
                'phone_number' => $request->phone_number,
            ]);

            putenv('MADELINE_PROGRAMMATIC_AUTH=1');
            $_ENV['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            $_SERVER['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            $_POST['type'] = 'phone';

            ob_start();
            ob_start();
            ob_start();

            $authResult = null;
            try {
                $adapter = new \Addons\MultiChannelSignalAddon\App\Adapters\TelegramMtprotoAdapter($source);
                $authResult = $adapter->startAuth();
            } catch (\Exception $e) {
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                Log::error("startAuth() exception", [
                    'message' => $e->getMessage(),
                ]);
                return redirect()->route('admin.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'phone',
                ])->with('error', 'Authentication error: ' . $e->getMessage());
            }

            $output = '';
            while (ob_get_level() > 0) {
                $output .= ob_get_clean();
            }

            if (!is_array($authResult) || !isset($authResult['type'])) {
                Log::error("Invalid startAuth() result", ['result' => $authResult]);
                return redirect()->route('admin.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'phone',
                ])->with('error', 'Invalid authentication response. Please try again.');
            }

            if ($authResult['type'] === 'code_required') {
                $_POST = $originalPost;

                $phoneCodeHash = $authResult['phone_code_hash'] ?? null;
                if (!$phoneCodeHash) {
                    return redirect()->route('admin.signal-sources.authenticate', [
                        'id' => $source->id,
                        'step' => 'phone',
                    ])->with('error', 'Failed to get verification code hash.');
                }

                $request->session()->put('admin_phone_code_hash', $phoneCodeHash);
                $request->session()->save();

                return redirect()->route('admin.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'code',
                ])->with('info', $authResult['message'] ?? 'Verification code sent.');
            }

            if ($authResult['type'] === 'error') {
                $_POST = $originalPost;
                return redirect()->route('admin.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'phone',
                ])->with('error', $authResult['message'] ?? 'Failed to send verification code.');
            }

            if ($authResult['type'] === 'success') {
                $_POST = $originalPost;
                return redirect()->route('admin.signal-sources.index')
                    ->with('success', 'Telegram account authenticated successfully!');
            }

            $_POST = $originalPost;
            return redirect()->route('admin.signal-sources.authenticate', [
                'id' => $source->id,
                'step' => 'phone',
            ])->with('error', 'Unexpected authentication response.');
        }

        if ($request->isMethod('post') && $step === 'code') {
            $request->session()->save();
            $request->validate(['code' => 'required|string']);

            $phoneCodeHash = $request->session()->get('admin_phone_code_hash') ?? $request->phone_code_hash;
            if (!$phoneCodeHash) {
                $request->session()->save();
                return redirect()->route('admin.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'phone',
                ])->with('error', 'Invalid session. Please start over.');
            }

            $source = $source->fresh();

            if (empty($source->config['phone_number'] ?? null)) {
                return redirect()->route('admin.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'phone',
                ])->with('error', 'Phone number not found.');
            }

            $_ENV['MADELINE_PROGRAMMATIC_AUTH'] = true;

            ob_start();
            ob_start();
            $result = $this->telegramMtprotoService->completeAuth($source, $request->code, $phoneCodeHash);
            $output = ob_get_clean();
            ob_end_clean();

            if ($result['type'] === 'success') {
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                $_POST = $originalPost;

                $request->session()->forget('admin_phone_code_hash');
                $request->session()->save();
                $request->session()->regenerateToken();

                return redirect()->route('admin.signal-sources.index')
                    ->with('success', 'Telegram account authenticated successfully!');
            }

            // Handle password requirement (2FA)
            if ($result['type'] === 'password_required') {
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                $_POST = $originalPost;

                $request->session()->put('admin_password_hint', $result['hint'] ?? '');
                $request->session()->save();

                return redirect()->route('admin.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'password',
                ])->with('info', $result['message'] ?? 'Two-factor authentication is enabled. Please enter your password.');
            }

            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $_POST = $originalPost;
            return redirect()->back()->with('error', $result['message']);
        }

        // Handle password step
        if ($request->isMethod('post') && $step === 'password') {
            $request->validate(['password' => 'required|string']);

            $source = $source->fresh();

            // Set programmatic auth mode BEFORE any MadelineProto calls
            putenv('MADELINE_PROGRAMMATIC_AUTH=1');
            $_ENV['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            $_SERVER['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            $_POST['type'] = 'password';
            $_POST['password'] = $request->password;

            // Start multiple output buffers to catch all HTML output
            ob_start();
            ob_start();
            ob_start();
            ob_start();
            
            $result = $this->telegramMtprotoService->completePasswordAuth($source, $request->password);
            
            // Clear ALL output buffers
            $output = '';
            while (ob_get_level() > 0) {
                $output .= ob_get_clean();
            }
            
            // Log if HTML was captured
            if (!empty($output) && (strpos($output, '<html') !== false || strpos($output, 'MadelineProto') !== false)) {
                Log::warning("MadelineProto HTML output captured in password auth", [
                    'source_id' => $source->id,
                    'output_length' => strlen($output),
                    'contains_error' => strpos($output, 'PASSWORD_HASH_INVALID') !== false || strpos($output, 'ERROR') !== false
                ]);
            }

            if ($result['type'] === 'success') {
                $_POST = $originalPost;

                $request->session()->forget('admin_password_hint');
                $request->session()->save();
                $request->session()->regenerateToken();

                return redirect()->route('admin.signal-sources.index')
                    ->with('success', 'Telegram account authenticated successfully!');
            }

            $_POST = $originalPost;
            
            // If password was invalid, show error with hint
            $errorMessage = $result['message'];
            if (strpos($errorMessage, 'Invalid password') !== false && !empty($source->config['password_hint'] ?? '')) {
                $errorMessage .= ' Hint: ' . $source->config['password_hint'];
            }
            
            return redirect()->back()->with('error', $errorMessage)->withInput();
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $_POST = $originalPost;

        return view('multi-channel-signal-addon::backend.signal-source.authenticate', $data);
    }

    /**
     * Update source status.
     */
    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $source = ChannelSource::findOrFail($id);

        $status = $request->input('status');
        if (!in_array($status, ['active', 'paused'], true)) {
            return redirect()->back()->with('error', 'Invalid status');
        }

        $source->update(['status' => $status]);

        $message = $status === 'active' ? 'Source resumed' : 'Source paused';
        return redirect()->back()->with('success', $message);
    }

    /**
     * Delete signal source.
     */
    public function destroy(int $id): RedirectResponse
    {
        $source = ChannelSource::findOrFail($id);

        // Cleanup MTProto session if exists
        if ($source->type === 'telegram_mtproto') {
            $sessionFile = storage_path('app/madelineproto/admin/' . $source->id . '.madeline');
            if (file_exists($sessionFile)) {
                @unlink($sessionFile);
            }
        }

        $source->delete();

        return redirect()->route('admin.signal-sources.index')
            ->with('success', 'Signal source deleted successfully');
    }

    /**
     * Test connection for a signal source.
     */
    public function testConnection(int $id): JsonResponse
    {
        try {
            $source = ChannelSource::findOrFail($id);
            $adapter = $this->getAdapter($source);

            if (!$adapter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unsupported source type: ' . $source->type,
                ], 400);
            }

            // Validate config first
            try {
                if (!$adapter->validateConfig($source->config ?? [])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid configuration. Please check your settings.',
                    ], 400);
                }
            } catch (\Exception $e) {
                Log::error("Config validation failed: " . $e->getMessage(), [
                    'source_id' => $id,
                    'exception' => $e,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Configuration validation error: ' . $e->getMessage(),
                ], 400);
            }

            // Test connection
            try {
                // For Telegram MTProto, check authentication status first
                if ($source->type === 'telegram_mtproto') {
                    // Check if authenticated
                    $isAuthenticated = ($source->config['authenticated'] ?? false) === true;
                    
                    if (!$isAuthenticated) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Telegram account not authenticated. Please complete authentication first.',
                        ], 400);
                    }
                    
                    // For MTProto, we need to ensure $_POST is set for the adapter
                    // The adapter checks $_POST to determine if it should start
                    if (empty($_POST)) {
                        $_POST = ['_token' => request()->header('X-CSRF-TOKEN')];
                    }
                }
                
                $connected = $adapter->connect($source);
            } catch (\Throwable $e) {
                Log::error("Connection test failed: " . $e->getMessage(), [
                    'source_id' => $id,
                    'source_type' => $source->type,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Connection test error: ' . $e->getMessage(),
                ], 500);
            }

            if ($connected) {
                // Update source status to 'active' on successful connection
                if ($source->status !== 'active') {
                    $source->update(['status' => 'active']);
                    $source->refresh();
                }
                
                // For Telegram MTProto, ensure authenticated flag is set
                if ($source->type === 'telegram_mtproto') {
                    $config = $source->config;
                    if (!($config['authenticated'] ?? false)) {
                        $config['authenticated'] = true;
                        $source->update(['config' => $config]);
                        $source->refresh();
                    }
                }
                
                try {
                    $details = $this->getConnectionDetails($source, $adapter);
                } catch (\Exception $e) {
                    Log::warning("Failed to get connection details: " . $e->getMessage(), [
                        'source_id' => $id,
                    ]);
                    $details = ['type' => $source->type, 'status' => $source->status];
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful!',
                    'details' => $details,
                ]);
            }

            // Update status to 'error' if connection failed
            if ($source->status !== 'error') {
                $source->update(['status' => 'error']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Connection failed. Please check your credentials and try again.',
            ], 400);

        } catch (\Exception $e) {
            Log::error("Test connection failed: " . $e->getMessage(), [
                'source_id' => $id,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
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
     * Build config array from request payload.
     */
    protected function buildConfig(array $data): array
    {
        $config = [];

        switch ($data['type']) {
            case 'api':
                $config = [
                    'webhook_url' => $data['webhook_url'] ?? null,
                    'secret_key' => $data['secret_key'] ?? null,
                ];
                break;
            case 'web_scrape':
                $config = [
                    'url' => $data['url'] ?? null,
                    'selector' => $data['selector'] ?? null,
                    'selector_type' => $data['selector_type'] ?? 'css',
                ];
                break;
            case 'rss':
                $config = [
                    'feed_url' => $data['feed_url'] ?? null,
                ];
                break;
            case 'trading_bot':
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
                break;
        }

        return $config;
    }
}

