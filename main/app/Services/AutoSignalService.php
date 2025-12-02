<?php

namespace App\Services;

use App\DTOs\ParsedSignalData;
use App\Models\ChannelMessage;
use App\Models\ChannelSource;
use App\Models\CurrencyPair;
use App\Models\Market;
use App\Models\Signal;
use App\Models\TimeFrame;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoSignalService
{
    /**
     * Create a signal from parsed message data.
     *
     * @param ParsedSignalData $parsedData
     * @param ChannelSource $channelSource
     * @param ChannelMessage $channelMessage
     * @return Signal|null
     */
    public function createFromParsedData(
        ParsedSignalData $parsedData,
        ChannelSource $channelSource,
        ChannelMessage $channelMessage
    ): ?Signal {
        if (!$parsedData->isValid()) {
            Log::warning("Invalid parsed data, missing fields: " . implode(', ', $parsedData->getMissingFields()));
            return null;
        }

        try {
            DB::beginTransaction();

            // Map currency pair
            $currencyPair = $this->findOrCreateCurrencyPair($parsedData->currency_pair);
            if (!$currencyPair) {
                throw new \Exception("Could not find or create currency pair: {$parsedData->currency_pair}");
            }

            // Map timeframe
            $timeframe = $this->findOrCreateTimeframe($parsedData->timeframe ?? $channelSource->default_timeframe_id);
            if (!$timeframe) {
                throw new \Exception("Could not find or create timeframe");
            }

            // Map market
            $market = $this->findOrCreateMarket($parsedData->market ?? $channelSource->default_market_id);
            if (!$market) {
                throw new \Exception("Could not find or create market");
            }

            // Validate prices
            if (!$this->validatePrice($parsedData->open_price)) {
                throw new \Exception("Invalid open price: {$parsedData->open_price}");
            }

            if ($parsedData->sl && !$this->validatePrice($parsedData->sl)) {
                throw new \Exception("Invalid stop loss: {$parsedData->sl}");
            }

            if ($parsedData->tp && !$this->validatePrice($parsedData->tp)) {
                throw new \Exception("Invalid take profit: {$parsedData->tp}");
            }

            // Create signal
            $signal = Signal::create([
                'title' => $parsedData->title ?? "Signal: {$parsedData->currency_pair} {$parsedData->direction}",
                'currency_pair_id' => $currencyPair->id,
                'time_frame_id' => $timeframe->id,
                'market_id' => $market->id,
                'open_price' => $parsedData->open_price,
                'sl' => $parsedData->sl ?? 0,
                'tp' => $parsedData->tp ?? 0,
                'direction' => $parsedData->direction,
                'description' => $parsedData->description,
                'is_published' => 0, // Draft
                'auto_created' => 1,
                'channel_source_id' => $channelSource->id,
                'message_hash' => $channelMessage->message_hash,
                'status' => 1,
                'published_date' => now(),
            ]);

            // Assign to default plan if set
            if ($channelSource->default_plan_id) {
                $signal->plans()->attach($channelSource->default_plan_id);
            }

            // Update channel message
            $channelMessage->update([
                'signal_id' => $signal->id,
                'parsed_data' => $parsedData->toArray(),
                'confidence_score' => $parsedData->confidence,
            ]);

            DB::commit();

            Log::info("Auto-created signal {$signal->id} from channel message {$channelMessage->id}");

            // Auto-publish if confidence >= threshold
            if ($parsedData->confidence >= $channelSource->auto_publish_confidence_threshold) {
                $this->autoPublish($signal);
            }

            return $signal;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create signal from parsed data: " . $e->getMessage(), [
                'exception' => $e,
                'parsed_data' => $parsedData->toArray(),
                'channel_source_id' => $channelSource->id,
            ]);

            return null;
        }
    }

    /**
     * Find or create currency pair.
     *
     * @param string|null $pairName
     * @return CurrencyPair|null
     */
    protected function findOrCreateCurrencyPair(?string $pairName): ?CurrencyPair
    {
        if (!$pairName) {
            return CurrencyPair::whereStatus(true)->first();
        }

        // Normalize pair name
        $pairName = strtoupper(trim($pairName));
        $pairName = str_replace('-', '/', $pairName);

        // Try to find existing
        $pair = CurrencyPair::where('name', $pairName)
            ->whereStatus(true)
            ->first();

        if ($pair) {
            return $pair;
        }

        // Try to create (if enabled in config)
        // For now, return first active pair as fallback
        return CurrencyPair::whereStatus(true)->first();
    }

    /**
     * Find or create timeframe.
     *
     * @param mixed $timeframe
     * @return TimeFrame|null
     */
    protected function findOrCreateTimeframe($timeframe): ?TimeFrame
    {
        // If it's an ID
        if (is_numeric($timeframe)) {
            return TimeFrame::where('id', $timeframe)->whereStatus(true)->first();
        }

        // If it's a string, try to match
        if (is_string($timeframe)) {
            $timeframe = strtoupper(trim($timeframe));
            
            // Map common timeframe formats
            $mapping = [
                'M1' => 'M1', '1MIN' => 'M1', '1M' => 'M1',
                'M5' => 'M5', '5MIN' => 'M5', '5M' => 'M5',
                'M15' => 'M15', '15MIN' => 'M15', '15M' => 'M15',
                'M30' => 'M30', '30MIN' => 'M30', '30M' => 'M30',
                'H1' => 'H1', '1H' => 'H1', '1HOUR' => 'H1',
                'H4' => 'H4', '4H' => 'H4', '4HOUR' => 'H4',
                'D1' => 'D1', '1D' => 'D1', '1DAY' => 'D1',
                'W1' => 'W1', '1W' => 'W1', '1WEEK' => 'W1',
            ];

            $normalized = $mapping[$timeframe] ?? $timeframe;
            
            $timeframeModel = TimeFrame::where('name', $normalized)
                ->whereStatus(true)
                ->first();

            if ($timeframeModel) {
                return $timeframeModel;
            }
        }

        // Fallback to first active timeframe
        return TimeFrame::whereStatus(true)->first();
    }

    /**
     * Find or create market.
     *
     * @param mixed $market
     * @return Market|null
     */
    protected function findOrCreateMarket($market): ?Market
    {
        // If it's an ID
        if (is_numeric($market)) {
            return Market::where('id', $market)->whereStatus(true)->first();
        }

        // If it's a string, try to find
        if (is_string($market)) {
            $marketModel = Market::where('name', $market)
                ->whereStatus(true)
                ->first();

            if ($marketModel) {
                return $marketModel;
            }
        }

        // Fallback to first active market
        return Market::whereStatus(true)->first();
    }

    /**
     * Validate price.
     *
     * @param float|null $price
     * @return bool
     */
    protected function validatePrice(?float $price): bool
    {
        if ($price === null) {
            return false;
        }

        // Price must be positive
        if ($price <= 0) {
            return false;
        }

        // Price must be reasonable (not too high)
        // This is configurable, but for now check if < 1,000,000
        if ($price > 1000000) {
            return false;
        }

        return true;
    }

    /**
     * Auto-publish signal if confidence is high enough.
     *
     * @param Signal $signal
     * @return void
     */
    protected function autoPublish(Signal $signal): void
    {
        try {
            $signalService = app(\App\Services\SignalService::class);
            $signalService->sent($signal->id);
            
            $signal->update([
                'published_date' => now(),
            ]);

            Log::info("Auto-published signal {$signal->id}");
        } catch (\Exception $e) {
            Log::error("Failed to auto-publish signal {$signal->id}: " . $e->getMessage());
        }
    }
}

