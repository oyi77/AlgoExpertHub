<?php

namespace App\Jobs;

use App\Models\ChannelMessage;
use App\Models\ChannelSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessChannelMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * The channel message instance.
     *
     * @var ChannelMessage
     */
    protected $channelMessage;

    /**
     * Create a new job instance.
     *
     * @param ChannelMessage $channelMessage
     */
    public function __construct(ChannelMessage $channelMessage)
    {
        $this->channelMessage = $channelMessage;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Reload the message to ensure we have the latest data
            $this->channelMessage->refresh();

            // Check if message is already processed
            if ($this->channelMessage->status !== 'pending') {
                Log::info("Channel message {$this->channelMessage->id} is not pending, skipping");
                return;
            }

            // Check for duplicates
            if ($this->isDuplicate()) {
                $this->channelMessage->markAsDuplicate();
                Log::info("Channel message {$this->channelMessage->id} is duplicate");
                return;
            }

            // Increment processing attempts
            $this->channelMessage->incrementAttempts();

            // Parse message using parsing pipeline
            $pipeline = app(\App\Parsers\ParsingPipeline::class);
            $parsedData = $pipeline->parse($this->channelMessage->raw_message);

            if ($parsedData && $parsedData->isValid()) {
                // Create signal from parsed data
                $autoSignalService = app(\App\Services\AutoSignalService::class);
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
                // Parsing failed, queue for manual review
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

    /**
     * Check if message is a duplicate.
     *
     * @return bool
     */
    protected function isDuplicate(): bool
    {
        // Check for existing signals with same hash in last 24 hours
        $existingSignal = \App\Models\Signal::where('message_hash', $this->channelMessage->message_hash)
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if ($existingSignal) {
            return true;
        }

        // Check for existing channel messages with same hash in last 24 hours
        $existingMessage = ChannelMessage::where('message_hash', $this->channelMessage->message_hash)
            ->where('id', '!=', $this->channelMessage->id)
            ->where('status', '!=', 'duplicate')
            ->where('created_at', '>=', now()->subDay())
            ->first();

        return $existingMessage !== null;
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error("ProcessChannelMessage job failed permanently for message {$this->channelMessage->id}", [
            'exception' => $exception,
            'channel_message_id' => $this->channelMessage->id,
        ]);

        $this->channelMessage->markAsFailed($exception->getMessage());
    }
}

