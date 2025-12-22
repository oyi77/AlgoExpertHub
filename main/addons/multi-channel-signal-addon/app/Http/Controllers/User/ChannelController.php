<?php

namespace Addons\MultiChannelSignalAddon\App\Http\Controllers\User;

use Addons\MultiChannelSignalAddon\App\Adapters\ApiAdapter;
use Addons\MultiChannelSignalAddon\App\Adapters\RssAdapter;
use Addons\MultiChannelSignalAddon\App\Adapters\TelegramAdapter;
use Addons\MultiChannelSignalAddon\App\Adapters\TelegramMtprotoAdapter;
use Addons\MultiChannelSignalAddon\App\Adapters\WebScrapeAdapter;
use Addons\MultiChannelSignalAddon\App\Http\Controllers\Controller;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Addons\MultiChannelSignalAddon\App\Services\TelegramChannelService;
use Addons\MultiChannelSignalAddon\App\Services\TelegramMtprotoService;
use App\Models\Market;
use App\Models\Plan;
use App\Models\TimeFrame;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ChannelController extends Controller
{
    public function __construct(
        protected TelegramChannelService $telegramService,
        protected TelegramMtprotoService $telegramMtprotoService
    ) {
    }

    public function index(Request $request): View
    {
        $data['title'] = 'My Channels';

        $query = ChannelSource::userOwned()
            ->where('user_id', Auth::id())
            ->with(['user', 'defaultPlan', 'defaultMarket', 'defaultTimeframe']);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $data['channels'] = $query->latest()->paginate(20);
        $data['stats'] = [
            'total' => ChannelSource::where('user_id', Auth::id())->count(),
            'active' => ChannelSource::where('user_id', Auth::id())->where('status', 'active')->count(),
            'paused' => ChannelSource::where('user_id', Auth::id())->where('status', 'paused')->count(),
            'error' => ChannelSource::where('user_id', Auth::id())->where('status', 'error')->count(),
        ];

        return view('multi-channel-signal-addon::user.channel.index', $data);
    }

    public function create(string $type = 'telegram'): View
    {
        $allowedTypes = ['telegram', 'telegram_mtproto', 'api', 'web_scrape', 'rss'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'telegram';
        }

        $data['title'] = 'Add ' . ucfirst(str_replace('_', ' ', $type)) . ' Channel';
        $data['type'] = $type;
        $data['plans'] = Plan::whereStatus(true)->get();
        $data['markets'] = Market::whereStatus(true)->get();
        $data['timeframes'] = TimeFrame::whereStatus(true)->get();

        return view('multi-channel-signal-addon::user.channel.create', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:telegram,telegram_mtproto,api,web_scrape,rss',
        ]);

        $payload = $request->all();
        $payload['user_id'] = Auth::id();

        try {
            $result = match ($payload['type']) {
                'telegram' => $this->storeTelegramChannel($payload),
                'telegram_mtproto' => $this->storeTelegramMtprotoChannel($payload),
                'api' => $this->storeApiChannel($payload),
                'web_scrape' => $this->storeWebScrapeChannel($payload),
                'rss' => $this->storeRssChannel($payload),
                default => ['type' => 'error', 'message' => 'Invalid channel type'],
            };

            if (in_array($result['type'], ['success', 'warning'], true)) {
                return redirect()->route('user.channels.index')
                    ->with('success', $result['message']);
            }

            if (in_array($result['type'], ['phone_required', 'code_required'], true)) {
                return redirect()->route('user.channels.authenticate', [
                    'id' => $result['channel_source']->id,
                    'step' => $result['step'] ?? 'phone',
                ])->with('info', $result['message']);
            }

            return redirect()->back()->with('error', $result['message'] ?? 'Failed to create channel');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Error: ' . $th->getMessage());
        }
    }

    protected function storeTelegramChannel(array $data): array
    {
        Validator::make($data, [
            'bot_token' => 'required|string',
            'chat_id' => 'nullable|string',
            'chat_username' => 'nullable|string',
        ])->validate();

        return $this->telegramService->createChannel($data);
    }

    protected function storeTelegramMtprotoChannel(array $data): array
    {
        Validator::make($data, [
            'api_id' => 'required|string',
            'api_hash' => 'required|string',
            'phone_number' => 'nullable|string',
            'channel_username' => 'nullable|string',
            'channel_id' => 'nullable|string',
        ])->validate();

        return $this->telegramMtprotoService->createChannel($data);
    }

    protected function storeApiChannel(array $data): array
    {
        Validator::make($data, [
            'webhook_url' => 'nullable|url',
            'secret_key' => 'nullable|string',
        ])->validate();

        $config = [
            'webhook_url' => $data['webhook_url'] ?? null,
            'secret_key' => $data['secret_key'] ?? null,
        ];

        $channelSource = new ChannelSource([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'type' => 'api',
            'config' => $config,
            'status' => 'active',
            'default_plan_id' => $data['default_plan_id'] ?? null,
            'default_market_id' => $data['default_market_id'] ?? null,
            'default_timeframe_id' => $data['default_timeframe_id'] ?? null,
            'auto_publish_confidence_threshold' => $data['auto_publish_confidence_threshold'] ?? 90,
        ]);

        $adapter = new ApiAdapter($channelSource);
        if (!$adapter->validateConfig($config)) {
            return ['type' => 'error', 'message' => 'Invalid configuration'];
        }

        $channelSource->save();

        if (empty($config['webhook_url'])) {
            $webhookUrl = $adapter->generateWebhookUrl();
            $config['webhook_url'] = $webhookUrl;
            $channelSource->update(['config' => $config]);

            return [
                'type' => 'success',
                'message' => 'API channel created. Use this webhook URL: ' . $webhookUrl,
                'channel_source' => $channelSource,
            ];
        }

        return [
            'type' => 'success',
            'message' => 'API channel created successfully',
            'channel_source' => $channelSource,
        ];
    }

    protected function storeWebScrapeChannel(array $data): array
    {
        Validator::make($data, [
            'url' => 'required|url',
            'selector' => 'required|string',
            'selector_type' => 'required|in:css,xpath',
        ])->validate();

        $config = [
            'url' => $data['url'],
            'selector' => $data['selector'],
            'selector_type' => $data['selector_type'],
        ];

        $channelSource = new ChannelSource([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'type' => 'web_scrape',
            'config' => $config,
            'status' => 'active',
            'default_plan_id' => $data['default_plan_id'] ?? null,
            'default_market_id' => $data['default_market_id'] ?? null,
            'default_timeframe_id' => $data['default_timeframe_id'] ?? null,
            'auto_publish_confidence_threshold' => $data['auto_publish_confidence_threshold'] ?? 90,
        ]);

        $adapter = new WebScrapeAdapter($channelSource);
        if (!$adapter->validateConfig($config)) {
            return ['type' => 'error', 'message' => 'Invalid URL or selector'];
        }

        $channelSource->save();

        if (!$adapter->connect($channelSource)) {
            $channelSource->update(['status' => 'error']);
            return ['type' => 'error', 'message' => 'Failed to connect to URL. Please check the URL and try again.'];
        }

        return [
            'type' => 'success',
            'message' => 'Web scraping channel created successfully',
            'channel_source' => $channelSource,
        ];
    }

    protected function storeRssChannel(array $data): array
    {
        Validator::make($data, [
            'feed_url' => 'required|url',
        ])->validate();

        $config = [
            'feed_url' => $data['feed_url'],
        ];

        $channelSource = new ChannelSource([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'type' => 'rss',
            'config' => $config,
            'status' => 'active',
            'default_plan_id' => $data['default_plan_id'] ?? null,
            'default_market_id' => $data['default_market_id'] ?? null,
            'default_timeframe_id' => $data['default_timeframe_id'] ?? null,
            'auto_publish_confidence_threshold' => $data['auto_publish_confidence_threshold'] ?? 90,
        ]);

        $adapter = new RssAdapter($channelSource);
        if (!$adapter->validateConfig($config)) {
            return ['type' => 'error', 'message' => 'Invalid feed URL'];
        }

        $channelSource->save();

        if (!$adapter->connect($channelSource)) {
            $channelSource->update(['status' => 'error']);
            return ['type' => 'error', 'message' => 'Failed to validate RSS feed. Please check the feed URL.'];
        }

        return [
            'type' => 'success',
            'message' => 'RSS feed channel created successfully',
            'channel_source' => $channelSource,
        ];
    }

    public function authenticate(int $id, Request $request): View|RedirectResponse
    {
        $channel = ChannelSource::where('user_id', Auth::id())
            ->where('type', 'telegram_mtproto')
            ->findOrFail($id);

        $step = $request->get('step', 'phone');
        $data = [
            'title' => 'Authenticate Telegram Account',
            'channel' => $channel,
            'step' => $step,
        ];

        if ($request->isMethod('post') && $step === 'phone') {
            $request->validate(['phone_number' => 'required|string']);

            // Get current config and update phone number
            $config = $channel->config ?? [];
            $config['phone_number'] = $request->phone_number;
            
            // Use forceFill and save to ensure config is properly saved
            $channel->forceFill(['config' => $config]);
            $channel->save();
            
            // Refresh to get the latest data from database
            $channel->refresh();
            
            // Log to verify phone number was saved
            \Log::info("Phone number saved (user channel)", [
                'channel_id' => $channel->id,
                'phone_number' => $request->phone_number,
                'config_after_save' => $channel->config ?? [],
                'phone_in_config' => $channel->config['phone_number'] ?? 'NOT FOUND'
            ]);

            // CRITICAL: Set programmatic auth mode BEFORE initializing adapter
            putenv('MADELINE_PROGRAMMATIC_AUTH=1');
            $_ENV['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            $_SERVER['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            
            // Store original POST data
            $originalPost = $_POST ?? [];
            $_POST['type'] = 'phone';
            
            // Use output buffering to catch any output
            ob_start();
            ob_start();
            ob_start();
            
            $authResult = null;
            try {
                $adapter = new \Addons\MultiChannelSignalAddon\App\Adapters\TelegramMtprotoAdapter($channel);
                $authResult = $adapter->startAuth();
                
                \Log::info("startAuth() result (user channel)", [
                    'type' => $authResult['type'] ?? 'unknown',
                    'message' => $authResult['message'] ?? 'no message',
                    'has_phone_code_hash' => isset($authResult['phone_code_hash'])
                ]);
            } catch (\Exception $e) {
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                \Log::error("startAuth() exception (user channel)", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->route('user.channels.authenticate', [
                    'id' => $channel->id,
                    'step' => 'phone',
                ])->with('error', 'Authentication error: ' . $e->getMessage());
            }
            
            // Get any captured output
            $output = '';
            while (ob_get_level() > 0) {
                $output .= ob_get_clean();
            }
            
            if (!empty($output)) {
                \Log::warning("Output captured during startAuth() (user channel)", [
                    'output_length' => strlen($output),
                    'contains_html' => strpos($output, '<html') !== false,
                ]);
            }
            
            if (!is_array($authResult) || !isset($authResult['type'])) {
                \Log::error("Invalid startAuth() result (user channel)", ['result' => $authResult]);
                return redirect()->route('user.channels.authenticate', [
                    'id' => $channel->id,
                    'step' => 'phone',
                ])->with('error', 'Invalid authentication response. Please try again.');
            }

            if ($authResult['type'] === 'code_required') {
                $_POST = $originalPost;
                
                $phoneCodeHash = $authResult['phone_code_hash'] ?? null;
                if (!$phoneCodeHash) {
                    \Log::error("code_required but no phone_code_hash (user channel)", ['result' => $authResult]);
                    return redirect()->route('user.channels.authenticate', [
                        'id' => $channel->id,
                        'step' => 'phone',
                    ])->with('error', 'Failed to get verification code hash. Please try again.');
                }
                
                $request->session()->put('phone_code_hash', $phoneCodeHash);
                $request->session()->save();

                return redirect()->route('user.channels.authenticate', [
                    'id' => $channel->id,
                    'step' => 'code',
                ])->with('info', $authResult['message'] ?? 'Verification code sent. Please check your Telegram app.');
            }

            if ($authResult['type'] === 'password_required') {
                $_POST = $originalPost;
                
                $phoneCodeHash = $authResult['phone_code_hash'] ?? null;
                if ($phoneCodeHash) {
                    $request->session()->put('phone_code_hash', $phoneCodeHash);
                    $request->session()->save();
                }
                
                return redirect()->route('user.channels.authenticate', [
                    'id' => $channel->id,
                    'step' => 'password',
                ])->with('info', $authResult['message'] ?? 'Two-factor authentication is enabled. Please enter your password.');
            }

            if ($authResult['type'] === 'error') {
                $_POST = $originalPost;
                return redirect()->route('user.channels.authenticate', [
                    'id' => $channel->id,
                    'step' => 'phone',
                ])->with('error', $authResult['message'] ?? 'Failed to send verification code.');
            }

            if ($authResult['type'] === 'success') {
                $_POST = $originalPost;
                return redirect()->route('user.channels.index')
                    ->with('success', 'Telegram account authenticated successfully!');
            }
            
            $_POST = $originalPost;
            return redirect()->route('user.channels.authenticate', [
                'id' => $channel->id,
                'step' => 'phone',
            ])->with('error', 'Unexpected authentication response. Please try again.');
        }

        if ($request->isMethod('post') && $step === 'code') {
            // CRITICAL: Save session before any operations
            $request->session()->save();
            
            $request->validate(['code' => 'required|string']);

            $phoneCodeHash = $request->session()->get('phone_code_hash') ?? $request->phone_code_hash;
            if (!$phoneCodeHash) {
                $request->session()->save();
                return redirect()->route('user.channels.authenticate', [
                    'id' => $channel->id,
                    'step' => 'phone',
                ])->with('error', 'Invalid session. Please start over.');
            }

            // CRITICAL: Refresh channel from database
            $channel = $channel->fresh();
            
            // Verify phone number exists
            if (empty($channel->config['phone_number'] ?? null)) {
                \Log::error("Phone number missing after refresh (user channel)", [
                    'channel_id' => $channel->id,
                    'config' => $channel->config
                ]);
                return redirect()->route('user.channels.authenticate', [
                    'id' => $channel->id,
                    'step' => 'phone',
                ])->with('error', 'Phone number not found. Please re-enter your phone number.');
            }

            // Prevent MadelineProto web UI
            $_ENV['MADELINE_PROGRAMMATIC_AUTH'] = true;
            $originalPost = $_POST ?? [];
            
            // Suppress output
            ob_start();
            ob_start();
            $result = $this->telegramMtprotoService->completeAuth($channel, $request->code, $phoneCodeHash);
            $output = ob_get_clean();
            ob_end_clean();
            
            if (!empty($output) && (strpos($output, '<html') !== false || strpos($output, 'MadelineProto') !== false)) {
                \Log::warning("MadelineProto web UI output suppressed (user channel)", ['output_length' => strlen($output)]);
            }

            if ($result['type'] === 'success') {
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                $_POST = $originalPost;
                
                $request->session()->forget('phone_code_hash');
                $request->session()->save();
                $request->session()->regenerateToken();

                return redirect()->route('user.channels.index')
                    ->with('success', 'Telegram account authenticated successfully!');
            }

            if ($result['type'] === 'password_required') {
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                $_POST = $originalPost;
                
                $request->session()->put('phone_code_hash', $phoneCodeHash);
                $request->session()->save();
                
                return redirect()->route('user.channels.authenticate', [
                    'id' => $channel->id,
                    'step' => 'password',
                ])->with('info', $result['message'] ?? 'Two-factor authentication is enabled. Please enter your password.');
            }

            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $_POST = $originalPost;
            
            return redirect()->back()->with('error', $result['message'] ?? 'Verification failed. Please try again.');
        }

        // Handle password step (2FA)
        if ($request->isMethod('post') && $step === 'password') {
            $request->session()->save();
            $request->validate(['password' => 'required|string']);

            $channel = $channel->fresh();

            // Set programmatic auth mode
            putenv('MADELINE_PROGRAMMATIC_AUTH=1');
            $_ENV['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            $_SERVER['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            $originalPost = $_POST ?? [];

            // Suppress output
            ob_start();
            ob_start();
            ob_start();
            
            $result = $this->telegramMtprotoService->completePasswordAuth($channel, $request->password);
            
            $output = '';
            while (ob_get_level() > 0) {
                $output .= ob_get_clean();
            }
            
            if (!empty($output) && (strpos($output, '<html') !== false || strpos($output, 'MadelineProto') !== false)) {
                \Log::warning("MadelineProto HTML output captured in password auth (user channel)", [
                    'output_length' => strlen($output)
                ]);
            }

            if ($result['type'] === 'success') {
                $_POST = $originalPost;
                $request->session()->save();
                $request->session()->regenerateToken();

                return redirect()->route('user.channels.index')
                    ->with('success', 'Telegram account authenticated successfully!');
            }

            $_POST = $originalPost;
            
            $errorMessage = $result['message'] ?? 'Password authentication failed.';
            if (strpos($errorMessage, 'Invalid password') !== false && !empty($channel->config['password_hint'] ?? '')) {
                $errorMessage .= ' Hint: ' . $channel->config['password_hint'];
            }
            
            return redirect()->back()->with('error', $errorMessage)->withInput();
        }

        return view('multi-channel-signal-addon::user.channel.authenticate', $data);
    }

    public function getDialogs(int $id): JsonResponse
    {
        $channel = ChannelSource::where('user_id', Auth::id())
            ->where('type', 'telegram_mtproto')
            ->findOrFail($id);

        return response()->json($this->telegramMtprotoService->getDialogs($channel));
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $channel = ChannelSource::where('user_id', Auth::id())->findOrFail($id);

        $status = $request->input('status');
        if (!in_array($status, ['active', 'paused'], true)) {
            return redirect()->back()->with('error', 'Invalid status');
        }

        $channel->update(['status' => $status]);

        $message = $status === 'active' ? 'Channel resumed' : 'Channel paused';
        return redirect()->back()->with('success', $message);
    }

    public function destroy(int $id): RedirectResponse
    {
        $channel = ChannelSource::where('user_id', Auth::id())->findOrFail($id);

        if ($channel->type === 'telegram') {
            $adapter = new TelegramAdapter($channel);
            $adapter->removeWebhook();
        } elseif ($channel->type === 'telegram_mtproto') {
            $sessionFile = storage_path('app/madelineproto/' . $channel->id . '.madeline');
            if (file_exists($sessionFile)) {
                @unlink($sessionFile);
            }
        }

        $channel->delete();

        return redirect()->route('user.channels.index')
            ->with('success', 'Channel deleted successfully');
    }
}
