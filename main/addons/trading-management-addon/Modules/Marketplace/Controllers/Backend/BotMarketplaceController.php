<?php

namespace Addons\TradingManagement\Modules\Marketplace\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\Marketplace\Services\MarketplaceService;
use Addons\TradingManagement\Modules\Marketplace\Models\{BotTemplate, SignalSourceTemplate, CompleteBot};
use Illuminate\Http\Request;

class BotMarketplaceController extends Controller
{
    protected $marketplaceService;

    public function __construct(MarketplaceService $marketplaceService)
    {
        $this->marketplaceService = $marketplaceService;
    }

    public function index(Request $request)
    {
        $type = $request->get('type', 'bot');
        
        $templates = match($type) {
            'signal' => $this->marketplaceService->browseSignalSources($request->all()),
            'complete' => $this->marketplaceService->browseCompleteBots($request->all()),
            default => $this->marketplaceService->browseBotTemplates($request->all()),
        };

        return view('trading-management::marketplace.backend.bots.index', compact('templates', 'type'));
    }

    public function show($id, Request $request)
    {
        $type = $request->get('type', 'bot');
        
        $template = match($type) {
            'signal' => SignalSourceTemplate::with('ratings')->findOrFail($id),
            'complete' => CompleteBot::with(['backtest', 'ratings'])->findOrFail($id),
            default => BotTemplate::with(['backtest', 'ratings'])->findOrFail($id),
        };

        return view('trading-management::marketplace.backend.bots.show', compact('template', 'type'));
    }

    public function approve($id, Request $request)
    {
        $type = $request->get('type', 'bot');
        $template = $this->getTemplate($type, $id);
        
        $template->update(['is_public' => true]);

        return redirect()->back()->with('success', 'Template approved successfully');
    }

    public function feature($id, Request $request)
    {
        $type = $request->get('type', 'bot');
        $template = $this->getTemplate($type, $id);
        
        $template->update(['is_featured' => !$template->is_featured]);

        return redirect()->back()->with('success', 'Template featured status updated');
    }

    public function destroy($id, Request $request)
    {
        $type = $request->get('type', 'bot');
        $template = $this->getTemplate($type, $id);
        
        $template->delete();

        return redirect()->route('admin.trading-management.marketplace.bots.index')->with('success', 'Template deleted successfully');
    }

    protected function getTemplate($type, $id)
    {
        return match($type) {
            'signal' => SignalSourceTemplate::findOrFail($id),
            'complete' => CompleteBot::findOrFail($id),
            default => BotTemplate::findOrFail($id),
        };
    }
}

