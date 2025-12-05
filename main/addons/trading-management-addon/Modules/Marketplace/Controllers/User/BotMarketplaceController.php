<?php

namespace Addons\TradingManagement\Modules\Marketplace\Controllers\User;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\Marketplace\Services\{MarketplaceService, TemplateCloneService, BacktestDisplayService};
use Addons\TradingManagement\Modules\Marketplace\Models\{BotTemplate, SignalSourceTemplate, CompleteBot, TemplateRating};
use Illuminate\Http\Request;

class BotMarketplaceController extends Controller
{
    protected $marketplaceService;
    protected $cloneService;
    protected $backtestService;

    public function __construct(
        MarketplaceService $marketplaceService,
        TemplateCloneService $cloneService,
        BacktestDisplayService $backtestService
    ) {
        $this->marketplaceService = $marketplaceService;
        $this->cloneService = $cloneService;
        $this->backtestService = $backtestService;
    }

    public function index(Request $request)
    {
        $type = $request->get('type', 'bot');
        
        if ($request->search) {
            $templates = $this->marketplaceService->search($type, $request->search, $request->all());
        } else {
            $templates = match($type) {
                'signal' => $this->marketplaceService->browseSignalSources($request->all()),
                'complete' => $this->marketplaceService->browseCompleteBots($request->all()),
                default => $this->marketplaceService->browseBotTemplates($request->all()),
            };
        }

        $featured = $this->marketplaceService->getFeatured($type, 6);

        return view('trading-management::marketplace.user.bots.index', compact('templates', 'featured', 'type'));
    }

    public function show($id, Request $request)
    {
        $type = $request->get('type', 'bot');
        
        $template = match($type) {
            'signal' => SignalSourceTemplate::with(['ratings' => fn($q) => $q->recent()->limit(10)])->findOrFail($id),
            'complete' => CompleteBot::with(['backtest', 'ratings' => fn($q) => $q->recent()->limit(10)])->findOrFail($id),
            default => BotTemplate::with(['backtest', 'ratings' => fn($q) => $q->recent()->limit(10)])->findOrFail($id),
        };

        $backtestData = null;
        if ($template->backtest) {
            $backtestData = $this->backtestService->formatForDisplay($template->backtest);
        }

        $userRating = TemplateRating::where('user_id', auth()->id())
            ->where('template_type', $type)
            ->where('template_id', $id)
            ->first();

        return view('trading-management::marketplace.user.bots.show', compact('template', 'type', 'backtestData', 'userRating'));
    }

    public function clone($id, Request $request)
    {
        $type = $request->get('type', 'bot');
        
        $request->validate([
            'name' => 'nullable|string|max:255',
            'activate' => 'boolean',
        ]);

        $result = $this->cloneService->clone($type, $id, auth()->id(), $request->all());

        if ($result['success']) {
            return redirect()->route('user.marketplace.my-clones')->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    public function myClones()
    {
        $clones = $this->cloneService->getUserClones(auth()->id());

        return view('trading-management::marketplace.user.clones.index', compact('clones'));
    }

    public function rate($id, Request $request)
    {
        $type = $request->get('type', 'bot');
        
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        TemplateRating::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'template_type' => $type,
                'template_id' => $id,
            ],
            [
                'rating' => $request->rating,
                'review' => $request->review,
                'verified_purchase' => $this->hasUserCloned($type, $id),
            ]
        );

        return redirect()->back()->with('success', 'Rating submitted successfully');
    }

    protected function hasUserCloned($type, $id): bool
    {
        return $this->cloneService->getUserClones(auth()->id(), $type)
            ->where('template_id', $id)
            ->isNotEmpty();
    }
}

