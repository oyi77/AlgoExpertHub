<?php

namespace App\Http\Controllers\User;

use App\Adapters\ApiAdapter;
use App\Adapters\RssAdapter;
use App\Adapters\TelegramAdapter;
use App\Adapters\WebScrapeAdapter;
use App\Http\Controllers\Controller;
use App\Models\ChannelSource;
use App\Services\TelegramChannelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChannelController extends Controller
{
    protected $telegramService;

    public function __construct(TelegramChannelService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Display a listing of user's channels.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $data['title'] = 'My Channels';

        $query = ChannelSource::where('user_id', Auth::id())
            ->with('user', 'defaultPlan', 'defaultMarket', 'defaultTimeframe');

        // Filter by type
        if ($request->type) {
            $query->where('type', $request->type);
        }

        // Filter by status
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

        return view('user.channel.index')->with($data);
    }

    /**
     * Show the form for creating a new channel.
     *
     * @param string $type
     * @return \Illuminate\View\View
     */
    public function create($type = 'telegram')
    {
        $data['title'] = 'Add ' . ucfirst($type) . ' Channel';
        $data['type'] = $type;
        $data['plans'] = \App\Models\Plan::whereStatus(true)->get();
        $data['markets'] = \App\Models\Market::whereStatus(true)->get();
        $data['timeframes'] = \App\Models\TimeFrame::whereStatus(true)->get();

        return view('user.channel.create')->with($data);
    }

    /**
     * Store a newly created channel.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:telegram,api,web_scrape,rss',
        ]);

        $type = $request->type;
        $data = $request->all();
        $data['user_id'] = Auth::id();

        try {
            switch ($type) {
                case 'telegram':
                    $result = $this->storeTelegramChannel($data);
                    break;
                case 'api':
                    $result = $this->storeApiChannel($data);
                    break;
                case 'web_scrape':
                    $result = $this->storeWebScrapeChannel($data);
                    break;
                case 'rss':
                    $result = $this->storeRssChannel($data);
                    break;
                default:
                    return redirect()->back()
                        ->with('error', 'Invalid channel type');
            }

            if ($result['type'] === 'success') {
                return redirect()->route('user.channels.index')
                    ->with('success', $result['message']);
            }

            return redirect()->back()
                ->with('error', $result['message'] ?? 'Failed to create channel');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Store Telegram channel.
     *
     * @param array $data
     * @return array
     */
    protected function storeTelegramChannel(array $data): array
    {
        $request = new Request($data);
        $request->validate([
            'bot_token' => 'required|string',
            'chat_id' => 'nullable|string',
            'chat_username' => 'nullable|string',
        ]);

        return $this->telegramService->createChannel($data);
    }

    /**
     * Store API channel.
     *
     * @param array $data
     * @return array
     */
    protected function storeApiChannel(array $data): array
    {
        $request = new Request($data);
        $request->validate([
            'webhook_url' => 'nullable|url',
            'secret_key' => 'nullable|string',
        ]);

        $adapter = new ApiAdapter(new ChannelSource());
        $config = [
            'webhook_url' => $data['webhook_url'] ?? null,
            'secret_key' => $data['secret_key'] ?? null,
        ];

        if (!$adapter->validateConfig($config)) {
            return [
                'type' => 'error',
                'message' => 'Invalid configuration'
            ];
        }

        // Generate webhook URL if not provided
        if (empty($config['webhook_url'])) {
            $channelSource = ChannelSource::create([
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
            $webhookUrl = $adapter->generateWebhookUrl();
            
            $config['webhook_url'] = $webhookUrl;
            $channelSource->update(['config' => $config]);

            return [
                'type' => 'success',
                'message' => 'API channel created. Use this webhook URL: ' . $webhookUrl,
                'channel_source' => $channelSource
            ];
        }

        $channelSource = ChannelSource::create([
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

        return [
            'type' => 'success',
            'message' => 'API channel created successfully',
            'channel_source' => $channelSource
        ];
    }

    /**
     * Store Web Scraping channel.
     *
     * @param array $data
     * @return array
     */
    protected function storeWebScrapeChannel(array $data): array
    {
        $request = new Request($data);
        $request->validate([
            'url' => 'required|url',
            'selector' => 'required|string',
            'selector_type' => 'required|in:css,xpath',
        ]);

        $adapter = new WebScrapeAdapter(new ChannelSource());
        $config = [
            'url' => $data['url'],
            'selector' => $data['selector'],
            'selector_type' => $data['selector_type'],
        ];

        if (!$adapter->validateConfig($config)) {
            return [
                'type' => 'error',
                'message' => 'Invalid URL or selector'
            ];
        }

        $channelSource = ChannelSource::create([
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

        // Test connection
        $adapter = new WebScrapeAdapter($channelSource);
        if (!$adapter->connect($channelSource)) {
            $channelSource->update(['status' => 'error']);
            return [
                'type' => 'error',
                'message' => 'Failed to connect to URL. Please check the URL and try again.'
            ];
        }

        return [
            'type' => 'success',
            'message' => 'Web scraping channel created successfully',
            'channel_source' => $channelSource
        ];
    }

    /**
     * Store RSS channel.
     *
     * @param array $data
     * @return array
     */
    protected function storeRssChannel(array $data): array
    {
        $request = new Request($data);
        $request->validate([
            'feed_url' => 'required|url',
        ]);

        $adapter = new RssAdapter(new ChannelSource());
        $config = [
            'feed_url' => $data['feed_url'],
        ];

        if (!$adapter->validateConfig($config)) {
            return [
                'type' => 'error',
                'message' => 'Invalid feed URL'
            ];
        }

        $channelSource = ChannelSource::create([
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

        // Test connection
        $adapter = new RssAdapter($channelSource);
        if (!$adapter->connect($channelSource)) {
            $channelSource->update(['status' => 'error']);
            return [
                'type' => 'error',
                'message' => 'Failed to validate RSS feed. Please check the feed URL.'
            ];
        }

        return [
            'type' => 'success',
            'message' => 'RSS feed channel created successfully',
            'channel_source' => $channelSource
        ];
    }

    /**
     * Update channel status (pause/resume).
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $channel = ChannelSource::where('user_id', Auth::id())->findOrFail($id);

        $status = $request->input('status');
        if (!in_array($status, ['active', 'paused'])) {
            return redirect()->back()->with('error', 'Invalid status');
        }

        $channel->update(['status' => $status]);

        $message = $status === 'active' ? 'Channel resumed' : 'Channel paused';
        return redirect()->back()->with('success', $message);
    }

    /**
     * Remove the specified channel.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $channel = ChannelSource::where('user_id', Auth::id())->findOrFail($id);

        // Clean up webhooks if needed
        if ($channel->type === 'telegram') {
            $adapter = new TelegramAdapter($channel);
            $adapter->removeWebhook();
        }

        $channel->delete();

        return redirect()->route('user.channels.index')
            ->with('success', 'Channel deleted successfully');
    }
}

