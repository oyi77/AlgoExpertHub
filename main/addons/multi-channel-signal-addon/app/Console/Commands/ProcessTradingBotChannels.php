<?php

namespace Addons\MultiChannelSignalAddon\App\Console\Commands;

use Addons\MultiChannelSignalAddon\App\Adapters\TradingBotAdapter;
use Addons\MultiChannelSignalAddon\App\Jobs\ProcessChannelMessage;
use Addons\MultiChannelSignalAddon\App\Models\ChannelMessage;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessTradingBotChannels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channel:process-trading-bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Trading Bot channels and fetch new signals';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Processing Trading Bot channels...');

        $channels = ChannelSource::where('type', 'trading_bot')
            ->where('status', 'active')
            ->get();

        if ($channels->isEmpty()) {
            $this->info('No active Trading Bot channels found.');
            return 0;
        }

        $processed = 0;
        $errors = 0;

        foreach ($channels as $channel) {
            try {
                $adapter = new TradingBotAdapter($channel);
                
                if (!$adapter->connect($channel)) {
                    $this->error("Failed to connect to channel: {$channel->name}");
                    $errors++;
                    continue;
                }

                $messages = $adapter->fetchMessages();

                foreach ($messages as $messageData) {
                    // Generate message hash
                    $messageHash = ChannelMessage::generateHash($messageData['text'], $messageData['timestamp']);

                    // Check for duplicate
                    $existingMessage = ChannelMessage::where('message_hash', $messageHash)
                        ->where('channel_source_id', $channel->id)
                        ->where('created_at', '>=', now()->subDay())
                        ->first();

                    if ($existingMessage) {
                        continue;
                    }

                    // Create channel message
                    $channelMessage = ChannelMessage::create([
                        'channel_source_id' => $channel->id,
                        'raw_message' => $messageData['text'],
                        'message_hash' => $messageHash,
                        'status' => 'pending',
                    ]);

                    // Dispatch job to process message
                    ProcessChannelMessage::dispatch($channelMessage);

                    $processed++;
                }

                // Update last processed
                $channel->updateLastProcessed();

                $this->info("Processed channel: {$channel->name} ({$messages->count()} new signals)");

            } catch (\Exception $e) {
                $this->error("Error processing channel {$channel->name}: " . $e->getMessage());
                Log::error("Trading Bot processing error for channel {$channel->id}: " . $e->getMessage());
                $channel->incrementError($e->getMessage());
                $errors++;
            }

            // Rate limiting - delay between channels
            sleep(1);
        }

        $this->info("Processing complete. Processed: {$processed} signals, Errors: {$errors}");

        return 0;
    }
}


