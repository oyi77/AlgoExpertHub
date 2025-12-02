<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\ChannelMessage;
use App\Models\Signal;
use App\Services\SignalService;
use Illuminate\Http\Request;

class ChannelSignalController extends Controller
{
    protected $signalService;

    public function __construct(SignalService $signalService)
    {
        $this->signalService = $signalService;
    }

    /**
     * Display a listing of auto-created signals.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $data['title'] = 'Review Auto-Created Signals';

        $query = Signal::where('auto_created', 1)
            ->where('is_published', 0)
            ->with(['channelSource', 'pair', 'time', 'market', 'plans']);

        // Filter by channel source
        if ($request->channel_source_id) {
            $query->where('channel_source_id', $request->channel_source_id);
        }

        // Filter by status
        if ($request->status) {
            if ($request->status === 'draft') {
                $query->where('is_published', 0);
            } elseif ($request->status === 'published') {
                $query->where('is_published', 1);
            }
        }

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $data['signals'] = $query->latest()->paginate(Helper::pagination());
        $data['channelSources'] = \App\Models\ChannelSource::with('user')->get();

        return view('backend.channel-signal.index')->with($data);
    }

    /**
     * Display the specified signal.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $data['title'] = 'Review Signal';

        $signal = Signal::with(['channelSource', 'pair', 'time', 'market', 'plans'])
            ->findOrFail($id);

        if (!$signal->isAutoCreated()) {
            return redirect()->route('admin.signals.index')
                ->with('error', 'Signal is not auto-created');
        }

        // Get original message
        $data['signal'] = $signal;
        $data['channelMessage'] = ChannelMessage::where('signal_id', $signal->id)->first();

        $data['plans'] = \App\Models\Plan::whereStatus(true)->get();
        $data['pairs'] = \App\Models\CurrencyPair::whereStatus(true)->get();
        $data['times'] = \App\Models\TimeFrame::whereStatus(true)->get();
        $data['markets'] = \App\Models\Market::whereStatus(true)->get();

        return view('backend.channel-signal.show')->with($data);
    }

    /**
     * Show the form for editing the signal.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $data['title'] = 'Edit Auto-Created Signal';

        $signal = Signal::with(['channelSource', 'pair', 'time', 'market', 'plans'])
            ->findOrFail($id);

        if (!$signal->isAutoCreated()) {
            return redirect()->route('admin.signals.index')
                ->with('error', 'Signal is not auto-created');
        }

        $data['signal'] = $signal;
        $data['channelMessage'] = ChannelMessage::where('signal_id', $signal->id)->first();

        $data['plans'] = \App\Models\Plan::whereStatus(true)->get();
        $data['pairs'] = \App\Models\CurrencyPair::whereStatus(true)->get();
        $data['times'] = \App\Models\TimeFrame::whereStatus(true)->get();
        $data['markets'] = \App\Models\Market::whereStatus(true)->get();

        return view('backend.channel-signal.edit')->with($data);
    }

    /**
     * Update the signal.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|max:255',
            'plans' => 'required|array',
            'currency_pair' => 'required|exists:currency_pairs,id',
            'time_frame' => 'required|exists:time_frames,id',
            'open_price' => 'required|numeric',
            'sl' => 'required|numeric',
            'tp' => 'required|numeric',
            'direction' => 'required|in:buy,sell',
            'market' => 'required|exists:markets,id'
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

    /**
     * Approve and publish the signal.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve($id)
    {
        $signal = Signal::findOrFail($id);

        if (!$signal->isAutoCreated()) {
            return redirect()->route('admin.channel-signals.index')
                ->with('error', 'Signal is not auto-created');
        }

        if ($signal->is_published) {
            return redirect()->route('admin.channel-signals.index')
                ->with('info', 'Signal is already published');
        }

        // Publish the signal
        $result = $this->signalService->sent($signal->id);

        if ($result['type'] === 'success') {
            // Update channel message status
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

    /**
     * Reject the signal.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        $signal = Signal::findOrFail($id);

        if (!$signal->isAutoCreated()) {
            return redirect()->route('admin.channel-signals.index')
                ->with('error', 'Signal is not auto-created');
        }

        // Update channel message with rejection reason
        $channelMessage = ChannelMessage::where('signal_id', $signal->id)->first();
        if ($channelMessage) {
            $channelMessage->markAsFailed($request->reason ?? 'Rejected by admin');
        }

        // Delete the signal
        $signal->delete();

        return redirect()->route('admin.channel-signals.index')
            ->with('success', 'Signal rejected and deleted');
    }

    /**
     * Bulk approve signals.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'signal_ids' => 'required|array',
            'signal_ids.*' => 'exists:signals,id'
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

    /**
     * Bulk reject signals.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkReject(Request $request)
    {
        $request->validate([
            'signal_ids' => 'required|array',
            'signal_ids.*' => 'exists:signals,id',
            'reason' => 'nullable|string|max:500'
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
}

