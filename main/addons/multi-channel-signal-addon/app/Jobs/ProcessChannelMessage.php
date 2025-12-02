<?php

namespace Addons\MultiChannelSignalAddon\App\Jobs;

use Addons\MultiChannelSignalAddon\App\Models\ChannelMessage;
use App\Models\Signal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessChannelMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;
    protected $channelMessage;

    public function __construct(ChannelMessage $channelMessage)
    {
        $this->channelMessage = $channelMessage;
    }

    public function handle()
    {
        try {
            $this->channelMessage->refresh();

            if ($this->channelMessage->status !== 'pending') {
                Log::info("Channel message {$this->channelMessage->id} is not pending, skipping");
                return;
            }

            if ($this->isDuplicate()) {
                $this->channelMessage->markAsDuplicate();
                Log::info("Channel message {$this->channelMessage->id} is duplicate");
                return;
            }

            $this->channelMessage->incrementAttempts();

            // Create pipeline with channel source context for pattern matching
            $channelSource = $this->channelMessage->channelSource;
            $pipeline = new \Addons\MultiChannelSignalAddon\App\Parsers\ParsingPipeline($channelSource);
            $parsedData = $pipeline->parse($this->channelMessage->raw_message);

            if ($parsedData && $parsedData->isValid()) {
                $autoSignalService = app(\Addons\MultiChannelSignalAddon\App\Services\AutoSignalService::class);
                $signal = $autoSignalService->createFromParsedData(
                    $parsedData,
                    $this->channelMessage->channelSource,
                    $this->channelMessage
                );

                if ($signal) {
                    $this->channelMessage->markAsProcessed($signal->id);
                    Log::info("Channel message {$this->channelMessage->id} processed successfully, created signal {$signal->id}");
                } else {
                    $this->channelMessage->markForManualReview('Failed to create signal from parsed data');
                    Log::warning("Channel message {$this->channelMessage->id} parsed but signal creation failed");
                }
            } else {
                $this->channelMessage->markForManualReview('Could not parse message');
                Log::info("Channel message {$this->channelMessage->id} could not be parsed, queued for manual review");
            }

        } catch (\Exception $e) {
            Log::error("Failed to process channel message {$this->channelMessage->id}: " . $e->getMessage(), [
                'exception' => $e,
                'channel_message_id' => $this->channelMessage->id,
            ]);

            $this->channelMessage->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    protected function isDuplicate(): bool
    {
        $existingSignal = Signal::where('message_hash', $this->channelMessage->message_hash)
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if ($existingSignal) {
            return true;
        }

        $existingMessage = ChannelMessage::where('message_hash', $this->channelMessage->message_hash)
            ->where('id', '!=', $this->channelMessage->id)
            ->where('status', '!=', 'duplicate')
            ->where('created_at', '>=', now()->subDay())
            ->first();

        return $existingMessage !== null;
    }

    public function failed(\Throwable $exception)
    {
        Log::error("ProcessChannelMessage job failed permanently for message {$this->channelMessage->id}", [
            'exception' => $exception,
            'channel_message_id' => $this->channelMessage->id,
        ]);

        $this->channelMessage->markAsFailed($exception->getMessage());
    }
}
