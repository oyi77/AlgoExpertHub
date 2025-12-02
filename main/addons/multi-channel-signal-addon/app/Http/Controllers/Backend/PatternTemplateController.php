<?php

namespace Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend;

use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Addons\MultiChannelSignalAddon\App\Models\MessageParsingPattern;
use Addons\MultiChannelSignalAddon\App\Services\PatternTemplateService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PatternTemplateController extends Controller
{
    protected PatternTemplateService $patternService;

    public function __construct(PatternTemplateService $patternService)
    {
        $this->patternService = $patternService;
    }

    /**
     * List patterns for a channel or all patterns.
     */
    public function index(Request $request)
    {
        try {
            $channelSourceId = $request->get('channel_source_id');
            
            $query = MessageParsingPattern::query();
            
            if ($channelSourceId) {
                $query->forChannel($channelSourceId);
            } else {
                // Show global patterns and all channel-specific patterns
                $query->where(function ($q) {
                    $q->whereNull('channel_source_id')
                      ->orWhereNotNull('channel_source_id');
                });
            }
            
            $patterns = $query->with('channelSource')
                ->orderedByPriority()
                ->paginate(20);

            $channels = ChannelSource::active()->get();
            $defaultTemplates = $this->patternService->getDefaultTemplates();
            
            // Ensure channelSourceId is set (can be null)
            $channelSourceId = $channelSourceId ?? null;
            $title = 'Pattern Templates';

            return view('multi-channel-signal-addon::backend.pattern-template.index', compact('patterns', 'channels', 'channelSourceId', 'title', 'defaultTemplates'));
        } catch (\Exception $e) {
            Log::error("PatternTemplateController::index error: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->route('admin.channel-forwarding.index')
                ->with('error', 'An error occurred while loading pattern templates: ' . $e->getMessage());
        }
    }

    /**
     * Show create form.
     */
    public function create(Request $request)
    {
        $channelSourceId = $request->get('channel_source_id');
        $channelSource = $channelSourceId ? ChannelSource::find($channelSourceId) : null;
        
        $channels = ChannelSource::active()->get();
        $defaultTemplates = $this->patternService->getDefaultTemplates();
        $title = 'Create Pattern Template';

        return view('multi-channel-signal-addon::backend.pattern-template.create', compact('channels', 'channelSource', 'defaultTemplates', 'title'));
    }

    /**
     * Store new pattern.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'channel_source_id' => 'nullable|exists:channel_sources,id',
            'pattern_type' => 'required|in:regex,template,ai_fallback',
            'pattern_config' => 'required',
            'priority' => 'nullable|integer|min:0|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            // Handle pattern_config - can be JSON string or array
            if (is_string($validated['pattern_config'])) {
                $decoded = json_decode($validated['pattern_config'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $validated['pattern_config'] = $decoded;
                } else {
                    throw new \InvalidArgumentException('Invalid JSON in pattern_config: ' . json_last_error_msg());
                }
            }
            
            // Ensure it's an array
            if (!is_array($validated['pattern_config'])) {
                throw new \InvalidArgumentException('pattern_config must be a valid JSON object or array');
            }
            
            $validated['user_id'] = null; // Admin-created patterns
            
            $pattern = $this->patternService->createPattern($validated);

            return redirect()
                ->route('admin.pattern-templates.index', ['channel_source_id' => $pattern->channel_source_id])
                ->with('success', 'Pattern template created successfully');
        } catch (\Exception $e) {
            Log::error("Failed to create pattern template: " . $e->getMessage());
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create pattern: ' . $e->getMessage()]);
        }
    }

    /**
     * Show edit form.
     */
    public function edit(MessageParsingPattern $patternTemplate)
    {
        $channels = ChannelSource::active()->get();
        $defaultTemplates = $this->patternService->getDefaultTemplates();
        $title = 'Edit Pattern Template';

        return view('multi-channel-signal-addon::backend.pattern-template.edit', compact('patternTemplate', 'channels', 'defaultTemplates', 'title'));
    }

    /**
     * Update pattern.
     */
    public function update(Request $request, MessageParsingPattern $patternTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pattern_type' => 'required|in:regex,template,ai_fallback',
            'pattern_config' => 'required|json',
            'priority' => 'nullable|integer|min:0|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $validated['pattern_config'] = json_decode($validated['pattern_config'], true);
            
            $pattern = $this->patternService->updatePattern($patternTemplate, $validated);

            return redirect()
                ->route('admin.pattern-templates.index', ['channel_source_id' => $pattern->channel_source_id])
                ->with('success', 'Pattern template updated successfully');
        } catch (\Exception $e) {
            Log::error("Failed to update pattern template: " . $e->getMessage());
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update pattern: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete pattern.
     */
    public function destroy(MessageParsingPattern $patternTemplate)
    {
        try {
            $channelSourceId = $patternTemplate->channel_source_id;
            $patternTemplate->delete();

            return redirect()
                ->route('admin.pattern-templates.index', ['channel_source_id' => $channelSourceId])
                ->with('success', 'Pattern template deleted successfully');
        } catch (\Exception $e) {
            Log::error("Failed to delete pattern template: " . $e->getMessage());
            
            return back()
                ->withErrors(['error' => 'Failed to delete pattern: ' . $e->getMessage()]);
        }
    }

    /**
     * Test pattern against sample message.
     */
    public function test(Request $request)
    {
        $validated = $request->validate([
            'pattern_config' => 'required|json',
            'sample_message' => 'required|string',
        ]);

        try {
            $patternConfig = json_decode($validated['pattern_config'], true);
            $result = $this->patternService->testPattern($patternConfig, $validated['sample_message']);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

