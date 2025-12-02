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
                    ->with('success', $result['message']);
            }

            if (in_array($result['type'], ['phone_required', 'code_required'], true)) {
                return redirect()->route('user.signal-sources.authenticate', [
                    'id' => $result['channel_source']->id,
                    'step' => $result['step'] ?? 'phone',
                ])->with('info', $result['message']);
            }

            return redirect()->back()->with('error', $result['message'] ?? 'Failed to create source');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Error: ' . $th->getMessage());
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

            $config = $source->config ?? [];
            $config['phone_number'] = $request->phone_number;
            $source->forceFill(['config' => $config]);
            $source->save();
            $source->refresh();

            $result = $this->telegramMtprotoService->createChannel([
                'user_id' => Auth::id(),
                'name' => $source->name,
                'api_id' => $config['api_id'],
                'api_hash' => $config['api_hash'],
                'phone_number' => $request->phone_number,
            ]);

            if ($result['type'] === 'code_required') {
                $request->session()->put('phone_code_hash', $result['phone_code_hash']);
                return redirect()->route('user.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'code',
                ])->with('info', $result['message']);
            }

            return redirect()->back()->with('error', $result['message']);
        }

        if ($request->isMethod('post') && $step === 'code') {
            $request->validate(['code' => 'required|string']);

            $phoneCodeHash = $request->session()->get('phone_code_hash') ?? $request->phone_code_hash;
            if (!$phoneCodeHash) {
                return redirect()->back()->with('error', 'Invalid session. Please start over.');
            }

            $source = $source->fresh();
            if (empty($source->config['phone_number'] ?? null)) {
                return redirect()->route('user.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'phone',
                ])->with('error', 'Phone number not found.');
            }

            $result = $this->telegramMtprotoService->completeAuth($source, $request->code, $phoneCodeHash);

            if ($result['type'] === 'success') {
                $request->session()->forget('phone_code_hash');
                return redirect()->route('user.signal-sources.index')
                    ->with('success', 'Telegram account authenticated successfully!');
            }

            return redirect()->back()->with('error', $result['message']);
        }

        return view('multi-channel-signal-addon::user.signal-source.authenticate', $data);
    }

    /**
     * Update source status.
     */
    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $source = ChannelSource::where('user_id', Auth::id())->findOrFail($id);

        $status = $request->input('status');
        if (!in_array($status, ['active', 'paused'], true)) {
            return redirect()->back()->with('error', 'Invalid status');
        }

        $source->update(['status' => $status]);

        $message = $status === 'active' ? 'Source resumed' : 'Source paused';
        return redirect()->back()->with('success', $message);
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
            ->with('success', 'Signal source deleted successfully');
    }
}

