<?php

namespace Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend;

use Addons\MultiChannelSignalAddon\App\Http\Controllers\Controller;
use Addons\MultiChannelSignalAddon\App\Models\ChannelMessage;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use App\Helpers\Helper\Helper;
use App\Models\CurrencyPair;
use App\Models\Market;
use App\Models\Plan;
use App\Models\Signal;
use App\Models\TimeFrame;
use App\Services\SignalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ChannelSignalController extends Controller
{
    protected SignalService $signalService;

    protected bool $hasSignalAddonColumns;

    public function __construct(SignalService $signalService)
    {
        $this->signalService = $signalService;
        $this->hasSignalAddonColumns = $this->detectSignalAddonColumns();
    }

    public function index(Request $request): View
    {
        $data['title'] = 'Channel Messages & Parsed Signals';
        $data['migrationPending'] = !$this->hasSignalAddonColumns;

        if (!$this->hasSignalAddonColumns) {
            $data['items'] = new LengthAwarePaginator(
                [],
                0,
                Helper::pagination(),
                (int) $request->input('page', 1),
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
            $data['channelSources'] = ChannelSource::with('user')->get();

            return view('multi-channel-signal-addon::backend.channel-signal.index', $data);
        }

        // Fetch all channel messages
        $messageQuery = ChannelMessage::with(['channelSource', 'signal'])
            ->latest();

        // Fetch parsed signals
        $signalQuery = Signal::where('auto_created', 1)
            ->with(['channelSource', 'pair', 'time', 'market', 'plans']);

        // Filter by channel source
        if ($request->channel_source_id) {
            $messageQuery->where('channel_source_id', $request->channel_source_id);
            $signalQuery->where('channel_source_id', $request->channel_source_id);
        }

        // Filter messages by status
        if ($request->message_status) {
            $messageQuery->where('status', $request->message_status);
        } else {
            // Default: exclude duplicates, show pending, failed, manual_review, and processed
            $messageQuery->whereIn('status', ['pending', 'failed', 'manual_review', 'processed']);
        }

        // Filter signals by publication status
        if ($request->status) {
            if ($request->status === 'draft') {
                $signalQuery->where('is_published', 0);
            } elseif ($request->status === 'published') {
                $signalQuery->where('is_published', 1);
            }
        }

        // Search filter
        if ($request->search) {
            $searchTerm = '%' . $request->search . '%';
            $messageQuery->where(function ($q) use ($searchTerm) {
                $q->where('raw_message', 'like', $searchTerm)
                  ->orWhere('error_message', 'like', $searchTerm);
            });
            $signalQuery->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Get all messages (including those with signals for reference)
        $allMessages = $messageQuery->get();
        
        // Get parsed signals
        $parsedSignals = $signalQuery->get();
        
        // Get signal IDs that are already shown
        $signalIds = $parsedSignals->pluck('id')->toArray();

        // Combine and sort by created_at
        $items = collect();
        
        // Add unparsed messages (those without signals)
        foreach ($allMessages as $message) {
            if (!$message->signal_id || !in_array($message->signal_id, $signalIds)) {
                $items->push([
                    'type' => 'message',
                    'id' => 'msg_' . $message->id,
                    'message' => $message,
                    'signal' => null,
                    'created_at' => $message->created_at,
                ]);
            }
        }
        
        // Add parsed signals (with their messages if available)
        foreach ($parsedSignals as $signal) {
            $message = ChannelMessage::where('signal_id', $signal->id)->first();
            $items->push([
                'type' => 'signal',
                'id' => 'sig_' . $signal->id,
                'message' => $message,
                'signal' => $signal,
                'created_at' => $signal->created_at,
            ]);
        }

        // Sort by created_at descending
        $items = $items->sortByDesc('created_at')->values();

        // Paginate manually
        $page = (int) $request->input('page', 1);
        $perPage = Helper::pagination();
        $total = $items->count();
        $items = $items->slice(($page - 1) * $perPage, $perPage)->values();

        $data['items'] = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
        
        $data['channelSources'] = ChannelSource::with('user')->get();

        return view('multi-channel-signal-addon::backend.channel-signal.index', $data);
    }

    public function show(int $id): View|RedirectResponse
    {
        if (!$this->hasSignalAddonColumns) {
            return $this->redirectForMissingColumns();
        }

        $data['title'] = 'Review Signal';

        $signal = Signal::with(['channelSource', 'pair', 'time', 'market', 'plans'])
            ->findOrFail($id);

        if (!$signal->isAutoCreated()) {
            return redirect()->route('admin.signals.index')
                ->with('error', 'Signal is not auto-created');
        }

        $data['signal'] = $signal;
        $data['channelMessage'] = ChannelMessage::where('signal_id', $signal->id)->first();

        $data['plans'] = Plan::whereStatus(true)->get();
        $data['pairs'] = CurrencyPair::whereStatus(true)->get();
        $data['times'] = TimeFrame::whereStatus(true)->get();
        $data['markets'] = Market::whereStatus(true)->get();

        return view('multi-channel-signal-addon::backend.channel-signal.show', $data);
    }

    public function edit(int $id): View|RedirectResponse
    {
        if (!$this->hasSignalAddonColumns) {
            return $this->redirectForMissingColumns();
        }

        $data['title'] = 'Edit Auto-Created Signal';

        $signal = Signal::with(['channelSource', 'pair', 'time', 'market', 'plans'])
            ->findOrFail($id);

        if (!$signal->isAutoCreated()) {
            return redirect()->route('admin.signals.index')
                ->with('error', 'Signal is not auto-created');
        }

        $data['signal'] = $signal;
        $data['channelMessage'] = ChannelMessage::where('signal_id', $signal->id)->first();

        $data['plans'] = Plan::whereStatus(true)->get();
        $data['pairs'] = CurrencyPair::whereStatus(true)->get();
        $data['times'] = TimeFrame::whereStatus(true)->get();
        $data['markets'] = Market::whereStatus(true)->get();

        return view('multi-channel-signal-addon::backend.channel-signal.edit', $data);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        if (!$this->hasSignalAddonColumns) {
            return $this->redirectForMissingColumns();
        }

        $request->validate([
            'title' => 'required|max:255',
            'plans' => 'required|array',
            'currency_pair' => 'required|exists:currency_pairs,id',
            'time_frame' => 'required|exists:time_frames,id',
            'open_price' => 'required|numeric',
            'sl' => 'required|numeric',
            'tp' => 'required|numeric',
            'direction' => 'required|in:buy,sell',
            'market' => 'required|exists:markets,id',
        ]);

        $signal = Signal::findOrFail($id);

        if (!$signal->isAutoCreated()) {
            return redirect()->route('admin.channel-signals.index')
                ->with('error', 'Signal is not auto-created');
        }

        $result = $this->signalService->update($request, $id);

        if ($result['type'] === 'success') {
            return redirect()->route('admin.channel-signals.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message'] ?? 'Failed to update signal');
    }

    public function approve(int $id): RedirectResponse
    {
        if (!$this->hasSignalAddonColumns) {
            return $this->redirectForMissingColumns();
        }

        $signal = Signal::findOrFail($id);

        if (!$signal->isAutoCreated()) {
            return redirect()->route('admin.channel-signals.index')
                ->with('error', 'Signal is not auto-created');
        }

        if ($signal->is_published) {
            return redirect()->route('admin.channel-signals.index')
                ->with('info', 'Signal is already published');
        }

        $result = $this->signalService->sent($signal->id);

        if ($result['type'] === 'success') {
            $channelMessage = ChannelMessage::where('signal_id', $signal->id)->first();
            if ($channelMessage) {
                $channelMessage->markAsProcessed($signal->id);
            }

            return redirect()->route('admin.channel-signals.index')
                ->with('success', 'Signal approved and published successfully');
        }

        return redirect()->back()
            ->with('error', $result['message'] ?? 'Failed to publish signal');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        if (!$this->hasSignalAddonColumns) {
            return $this->redirectForMissingColumns();
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $signal = Signal::findOrFail($id);

        if (!$signal->isAutoCreated()) {
            return redirect()->route('admin.channel-signals.index')
                ->with('error', 'Signal is not auto-created');
        }

        $channelMessage = ChannelMessage::where('signal_id', $signal->id)->first();
        if ($channelMessage) {
            $channelMessage->markAsFailed($request->reason ?? 'Rejected by admin');
        }

        $signal->delete();

        return redirect()->route('admin.channel-signals.index')
            ->with('success', 'Signal rejected and deleted');
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        if (!$this->hasSignalAddonColumns) {
            return $this->redirectForMissingColumns();
        }

        $request->validate([
            'signal_ids' => 'required|array',
            'signal_ids.*' => 'exists:signals,id',
        ]);

        $signals = Signal::whereIn('id', $request->signal_ids)
            ->where('auto_created', 1)
            ->where('is_published', 0)
            ->get();

        $approved = 0;
        foreach ($signals as $signal) {
            $result = $this->signalService->sent($signal->id);
            if ($result['type'] === 'success') {
                $approved++;

                $channelMessage = ChannelMessage::where('signal_id', $signal->id)->first();
                if ($channelMessage) {
                    $channelMessage->markAsProcessed($signal->id);
                }
            }
        }

        return redirect()->route('admin.channel-signals.index')
            ->with('success', "Approved and published {$approved} signals");
    }

    public function bulkReject(Request $request): RedirectResponse
    {
        if (!$this->hasSignalAddonColumns) {
            return $this->redirectForMissingColumns();
        }

        $request->validate([
            'signal_ids' => 'required|array',
            'signal_ids.*' => 'exists:signals,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $signals = Signal::whereIn('id', $request->signal_ids)
            ->where('auto_created', 1)
            ->get();

        $rejected = 0;
        foreach ($signals as $signal) {
            $channelMessage = ChannelMessage::where('signal_id', $signal->id)->first();
            if ($channelMessage) {
                $channelMessage->markAsFailed($request->reason ?? 'Bulk rejected by admin');
            }

            $signal->delete();
            $rejected++;
        }

        return redirect()->route('admin.channel-signals.index')
            ->with('success', "Rejected and deleted {$rejected} signals");
    }
    protected function detectSignalAddonColumns(): bool
    {
        return Schema::hasColumn('signals', 'auto_created')
            && Schema::hasColumn('signals', 'channel_source_id')
            && Schema::hasColumn('signals', 'message_hash');
    }

    protected function redirectForMissingColumns(): RedirectResponse
    {
        return redirect()->route('admin.channel-signals.index')
            ->with('error', 'Kolom auto_created belum tersedia. Jalankan migrasi addon Multi-Channel Signal (`php artisan migrate --path=main/addons/multi-channel-signal-addon/database/migrations`).');
    }
}

