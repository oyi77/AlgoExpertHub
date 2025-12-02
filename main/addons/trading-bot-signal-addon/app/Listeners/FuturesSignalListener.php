<?php

namespace Addons\TradingBotSignalAddon\App\Listeners;

use Addons\TradingBotSignalAddon\App\Services\SignalProcessorService;
use Illuminate\Support\Facades\Log;

class FuturesSignalListener
{
    protected $processor;

    public function __construct(SignalProcessorService $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Handle futures signal
     */
    public function handle(array $signal): void
    {
        try {
            // Check if it's a futures signal
            if ($this->isFuturesSignal($signal)) {
                $this->processor->processSignal($signal);
                Log::info('Futures signal processed', ['signal_id' => $signal['id'] ?? null]);
            }
        } catch (\Exception $e) {
            Log::error('FuturesSignalListener error: ' . $e->getMessage(), [
                'signal' => $signal
            ]);
        }
    }

    /**
     * Check if signal is a futures signal
     */
    protected function isFuturesSignal(array $signal): bool
    {
        return !empty($signal['type']) && $signal['type'] === 'futures' ||
               (!empty($signal['futures']) && $signal['futures'] === true);
    }
}

