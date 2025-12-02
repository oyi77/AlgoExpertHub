<?php

namespace Addons\TradingBotSignalAddon\App\Listeners;

use Addons\TradingBotSignalAddon\App\Services\SignalProcessorService;
use Illuminate\Support\Facades\Log;

class SpotSignalListener
{
    protected $processor;

    public function __construct(SignalProcessorService $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Handle spot signal
     */
    public function handle(array $signal): void
    {
        try {
            // Check if it's a spot signal
            if ($this->isSpotSignal($signal)) {
                $this->processor->processSignal($signal);
                Log::info('Spot signal processed', ['signal_id' => $signal['id'] ?? null]);
            }
        } catch (\Exception $e) {
            Log::error('SpotSignalListener error: ' . $e->getMessage(), [
                'signal' => $signal
            ]);
        }
    }

    /**
     * Check if signal is a spot signal
     */
    protected function isSpotSignal(array $signal): bool
    {
        return empty($signal['type']) || 
               $signal['type'] === 'spot' || 
               (!isset($signal['futures']) || $signal['futures'] === false);
    }
}

