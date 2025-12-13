<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Signal;

class ChannelSignalController extends Controller
{

    /**
     * List channel signals (auto-created signals)
     */
    public function index(Request $request): JsonResponse
    {
        $signals = Signal::autoCreated()
            ->with(['pair', 'time', 'market', 'channelSource', 'plans'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $signals
        ]);
    }

    /**
     * Show channel signal details
     */
    public function show($id): JsonResponse
    {
        $signal = Signal::autoCreated()
            ->with(['pair', 'time', 'market', 'channelSource', 'plans'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $signal
        ]);
    }

    /**
     * Update channel signal
     */
    public function update(Request $request, $id): JsonResponse
    {
        $signal = Signal::autoCreated()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'currency_pair_id' => 'sometimes|exists:currency_pairs,id',
            'time_frame_id' => 'sometimes|exists:time_frames,id',
            'market_id' => 'sometimes|exists:markets,id',
            'open_price' => 'sometimes|numeric',
            'sl' => 'sometimes|numeric',
            'tp' => 'sometimes|numeric',
            'direction' => 'sometimes|in:buy,sell,long,short',
        ]);

        $signal->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Signal updated successfully',
            'data' => $signal->load(['pair', 'time', 'market', 'channelSource'])
        ]);
    }

    /**
     * Approve channel signal (publish it)
     */
    public function approve($id): JsonResponse
    {
        try {
            $signal = Signal::autoCreated()->findOrFail($id);
            
            if ($signal->is_published) {
                return response()->json([
                    'success' => false,
                    'message' => 'Signal is already published'
                ], 400);
            }

            // Update signal
            $signal->update([
                'is_published' => 1,
                'published_date' => now()
            ]);

            // Distribute signal to users
            \App\Services\SignalService::sent($signal->id);

            return response()->json([
                'success' => true,
                'message' => 'Signal approved and published successfully',
                'data' => $signal->load(['pair', 'time', 'market'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve signal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject channel signal
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $signal = Signal::autoCreated()->findOrFail($id);

        // Optionally delete or mark as rejected
        // For now, we'll just delete it
        $signal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Signal rejected and removed successfully'
        ]);
    }

    /**
     * Bulk approve signals
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'signal_ids' => 'required|array',
            'signal_ids.*' => 'exists:signals,id'
        ]);

        $signals = Signal::autoCreated()
            ->whereIn('id', $validated['signal_ids'])
            ->where('is_published', 0)
            ->get();

        $approved = 0;
        foreach ($signals as $signal) {
            try {
                $signal->update([
                    'is_published' => 1,
                    'published_date' => now()
                ]);
                \App\Services\SignalService::sent($signal->id);
                $approved++;
            } catch (\Exception $e) {
                \Log::error('Failed to approve signal ' . $signal->id . ': ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Approved {$approved} of " . count($validated['signal_ids']) . " signals",
            'data' => ['approved' => $approved, 'total' => count($validated['signal_ids'])]
        ]);
    }

    /**
     * Bulk reject signals
     */
    public function bulkReject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'signal_ids' => 'required|array',
            'signal_ids.*' => 'exists:signals,id'
        ]);

        $deleted = Signal::autoCreated()
            ->whereIn('id', $validated['signal_ids'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "Rejected and removed {$deleted} signals",
            'data' => ['deleted' => $deleted]
        ]);
    }
}

