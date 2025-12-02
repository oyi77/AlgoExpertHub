<?php

namespace Addons\TradingExecutionEngine\App\Jobs;

use Addons\TradingExecutionEngine\App\Services\SignalExecutionService;
use App\Models\Signal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteSignalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Signal $signal;
    protected int $connectionId;

    /**
     * Create a new job instance.
     */
    public function __construct(Signal $signal, int $connectionId)
    {
        $this->signal = $signal;
        $this->connectionId = $connectionId;
    }

    /**
     * Execute the job.
     */
    public function handle(SignalExecutionService $executionService): void
    {
        try {
            // Get AI decision from channel message (if auto-created signal)
            $options = $this->getExecutionOptions();

            $result = $executionService->executeSignal($this->signal, $this->connectionId, $options);

            if (!$result['success']) {
                Log::warning("Signal execution failed", [
                    'signal_id' => $this->signal->id,
                    'connection_id' => $this->connectionId,
                    'message' => $result['message'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Signal execution job error", [
                'signal_id' => $this->signal->id,
                'connection_id' => $this->connectionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get execution options including AI decision (Sprint 2: AI Integration).
     */
    protected function getExecutionOptions(): array
    {
        $options = [];

        // If signal is auto-created, try to get AI decision from channel message
        if ($this->signal->auto_created && $this->signal->channel_source_id) {
            try {
                if (class_exists(\Addons\MultiChannelSignalAddon\App\Models\ChannelMessage::class)) {
                    $channelMessage = \Addons\MultiChannelSignalAddon\App\Models\ChannelMessage::where('signal_id', $this->signal->id)
                        ->where('channel_source_id', $this->signal->channel_source_id)
                        ->first();

                    if ($channelMessage && isset($channelMessage->parsed_data['ai_evaluation'])) {
                        $aiEvaluation = $channelMessage->parsed_data['ai_evaluation'];
                        
                        // If AI decision says to execute, apply risk factor as size multiplier
                        if (isset($aiEvaluation['execute']) && $aiEvaluation['execute']) {
                            $adjustedRiskFactor = $aiEvaluation['adjusted_risk_factor'] ?? 1.0;
                            
                            // Apply as size multiplier (0.0 to 1.0)
                            if ($adjustedRiskFactor < 1.0) {
                                $options['size_multiplier'] = $adjustedRiskFactor;
                                Log::info("Applying AI risk factor to position size", [
                                    'signal_id' => $this->signal->id,
                                    'risk_factor' => $adjustedRiskFactor,
                                ]);
                            }
                        } else {
                            // AI rejected, but if we're here, signal was published anyway
                            // This shouldn't happen if filter/AI hooks work correctly, but fail-safe
                            Log::warning("Signal execution attempted but AI evaluation says do not execute", [
                                'signal_id' => $this->signal->id,
                                'ai_reason' => $aiEvaluation['reason'] ?? 'Unknown',
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to get AI decision for execution", [
                    'signal_id' => $this->signal->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $options;
    }
}

